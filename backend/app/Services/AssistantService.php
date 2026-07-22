<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Exceptions\AppointmentConflictException;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AssistantService
{
    private const MAX_TOOL_ROUNDS = 4;

    public function __construct(private BookingService $bookingService) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{message: string}
     */
    public function reply(User $client, array $history): array
    {
        $apiKey = config('services.gemini.api_key');

        if (! $apiKey) {
            return ['message' => 'O assistente de IA ainda não foi configurado neste ambiente.'];
        }

        $contents = $this->toContents($history);

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $parts = collect($this->callGemini($apiKey, $contents));

            $functionCallPart = $parts->first(fn (array $part) => isset($part['functionCall']));

            if (! $functionCallPart) {
                $text = $parts->pluck('text')->filter()->implode('');

                return ['message' => $text !== '' ? $text : 'Não entendi, pode reformular?'];
            }

            $name = $functionCallPart['functionCall']['name'];
            $args = $functionCallPart['functionCall']['args'] ?? [];

            // args vazio vira [] no PHP, e json_encode manda isso como lista, não objeto.
            // O Gemini exige um objeto ({}), senão rejeita a rodada seguinte.
            if (($functionCallPart['functionCall']['args'] ?? null) === []) {
                $functionCallPart['functionCall']['args'] = (object) [];
            }

            // O part original (com thoughtSignature) precisa voltar intacto, senão o Gemini
            // rejeita a próxima chamada com "missing a thought_signature".
            $contents[] = ['role' => 'model', 'parts' => [$functionCallPart]];
            $contents[] = [
                'role' => 'user',
                'parts' => [['functionResponse' => ['name' => $name, 'response' => $this->executeTool($client, $name, $args)]]],
            ];
        }

        return ['message' => 'Não consegui concluir o agendamento agora. Pode tentar de novo?'];
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array<string, mixed>>
     */
    private function toContents(array $history): array
    {
        return array_map(fn (array $message) => [
            'role' => $message['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $message['content']]],
        ], $history);
    }

    /**
     * @param  array<int, array<string, mixed>>  $contents
     * @return array<int, array<string, mixed>>
     */
    private function callGemini(string $apiKey, array $contents): array
    {
        $model = config('services.gemini.model');

        $response = Http::timeout(20)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => $contents,
                'tools' => [['functionDeclarations' => $this->toolDeclarations()]],
                'systemInstruction' => ['parts' => [['text' => $this->systemPrompt()]]],
            ]
        );

        if ($response->failed()) {
            Log::warning('Falha ao chamar a API do Gemini.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Falha ao consultar o assistente de IA.');
        }

        return $response->json('candidates.0.content.parts') ?? [];
    }

    private function systemPrompt(): string
    {
        $now = Carbon::now();

        return <<<PROMPT
            Você é o assistente de agendamento do Zelo, um sistema de agendamento online.
            Ajude o cliente a escolher um serviço e um horário livre, e crie o agendamento
            quando ele confirmar. Sempre confira os horários livres com a ferramenta antes
            de sugerir um horário. Nunca invente serviços, preços ou horários que não
            vieram das ferramentas. Hoje é {$now->translatedFormat('l, d/m/Y')}, agora são
            {$now->format('H:i')}. Responda sempre em português do Brasil, de forma breve, em
            texto puro, sem markdown (sem **negrito**, sem listas com *).
            PROMPT;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function toolDeclarations(): array
    {
        return [
            [
                'name' => 'list_services',
                'description' => 'Lista os serviços ativos disponíveis, com duração e preço.',
            ],
            [
                'name' => 'check_available_slots',
                'description' => 'Lista os horários livres de um serviço em uma data.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'service_id' => ['type' => 'INTEGER', 'description' => 'ID do serviço'],
                        'date' => ['type' => 'STRING', 'description' => 'Data no formato YYYY-MM-DD'],
                    ],
                    'required' => ['service_id', 'date'],
                ],
            ],
            [
                'name' => 'book_appointment',
                'description' => 'Cria o agendamento depois que o cliente confirmar o serviço e o horário.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'service_id' => ['type' => 'INTEGER', 'description' => 'ID do serviço'],
                        'start_at' => ['type' => 'STRING', 'description' => 'Data e hora de início no formato YYYY-MM-DD HH:mm'],
                        'notes' => ['type' => 'STRING', 'description' => 'Observações opcionais do cliente'],
                    ],
                    'required' => ['service_id', 'start_at'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function executeTool(User $client, string $name, array $args): array
    {
        return match ($name) {
            'list_services' => $this->listServices(),
            'check_available_slots' => $this->checkAvailableSlots($args),
            'book_appointment' => $this->createBooking($client, $args),
            default => ['error' => "Ferramenta desconhecida: {$name}"],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function listServices(): array
    {
        /** @var Collection<int, Service> $services */
        $services = Service::query()->where('active', true)->get(['id', 'name', 'duration_minutes', 'price']);

        return ['services' => $services->map(fn (Service $service) => [
            'id' => $service->id,
            'name' => $service->name,
            'duration_minutes' => $service->duration_minutes,
            'price' => (float) $service->price,
        ])->all()];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function findService(array $args): ?Service
    {
        if (! isset($args['service_id']) || ! is_numeric($args['service_id'])) {
            return null;
        }

        return Service::query()->find((int) $args['service_id']);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function checkAvailableSlots(array $args): array
    {
        $service = $this->findService($args);

        if (! $service) {
            return ['error' => 'Serviço não encontrado.'];
        }

        $slots = $this->bookingService->availableSlots($service, (string) $args['date']);

        return ['slots' => array_map(fn (Carbon $slot) => $slot->format('Y-m-d H:i'), $slots)];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function createBooking(User $client, array $args): array
    {
        $service = $this->findService($args);

        if (! $service) {
            return ['error' => 'Serviço não encontrado.'];
        }

        try {
            $appointment = $this->bookingService->book(
                client: $client,
                service: $service,
                startAt: Carbon::parse((string) ($args['start_at'] ?? '')),
                notes: isset($args['notes']) ? (string) $args['notes'] : null,
                source: AppointmentSource::AiChat,
            );
        } catch (AppointmentConflictException $exception) {
            return ['error' => $exception->getMessage()];
        }

        return [
            'success' => true,
            'appointment_id' => $appointment->id,
            'start_at' => $appointment->start_at->format('Y-m-d H:i'),
        ];
    }
}
