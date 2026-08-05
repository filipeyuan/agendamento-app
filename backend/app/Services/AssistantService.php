<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Exceptions\AppointmentActionNotAllowedException;
use App\Exceptions\AppointmentConflictException;
use App\Exceptions\InvalidStaffAssignmentException;
use App\Models\Appointment;
use App\Models\Business;
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

    public function __construct(
        private BookingService $bookingService,
        private EmbeddingService $embeddingService,
        private AppointmentNotifier $notifier,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{message: string}
     */
    public function reply(User $client, array $history, Business $business): array
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
                'parts' => [['functionResponse' => ['name' => $name, 'response' => $this->executeTool($client, $name, $args, $business)]]],
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
            Sua função é ajudar o cliente a escolher um serviço, verificar horário livre,
            criar, cancelar ou remarcar um agendamento, e responder perguntas sobre o
            estabelecimento usando a base de conhecimento dele. Sempre confira os horários
            livres com a ferramenta antes de sugerir um horário. Nunca invente serviços,
            preços, horários, comodidades, produtos ou qualquer outra informação sobre o
            estabelecimento que não veio das ferramentas.
            Se o cliente perguntar algo fora do fluxo de agendamento (produtos, comodidades,
            formas de pagamento aceitas no local, estacionamento, etc.), use a ferramenta
            search_knowledge_base antes de responder. Se ela não retornar nada relevante,
            diga que isso precisa ser confirmado direto com o estabelecimento. Nunca invente
            uma resposta que não veio da base de conhecimento.
            Se o histórico da conversa já mostra que um agendamento foi criado com sucesso pro
            pedido atual, não verifique disponibilidade de novo nem trate como pendente: só
            confirme o que já foi feito.
            Se o serviço tiver profissionais vinculados (campo "staff" na lista de serviços),
            pergunte qual profissional o cliente prefere antes de checar disponibilidade ou
            criar o agendamento, e use o "staff_id" escolhido nas ferramentas. Nunca invente
            nem escolha um profissional por conta própria.
            Pra cancelar ou remarcar um agendamento, use list_my_appointments pra descobrir o
            appointment_id certo, confirme com o cliente qual agendamento exatamente (serviço
            e data) antes de agir, e só chame cancel_appointment/reschedule_appointment depois
            que o cliente confirmar explicitamente.
            Hoje é {$now->translatedFormat('l, d/m/Y')}, agora são
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
                        'staff_id' => ['type' => 'INTEGER', 'description' => 'ID do profissional escolhido, obrigatório se o serviço tiver profissionais vinculados'],
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
                        'staff_id' => ['type' => 'INTEGER', 'description' => 'ID do profissional escolhido, obrigatório se o serviço tiver profissionais vinculados'],
                    ],
                    'required' => ['service_id', 'start_at'],
                ],
            ],
            [
                'name' => 'list_my_appointments',
                'description' => 'Lista os próximos agendamentos do cliente atual, com id, serviço, profissional e data.',
            ],
            [
                'name' => 'cancel_appointment',
                'description' => 'Cancela um agendamento existente do cliente, depois que ele confirmar qual e que realmente quer cancelar.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'appointment_id' => ['type' => 'INTEGER', 'description' => 'ID do agendamento a cancelar'],
                    ],
                    'required' => ['appointment_id'],
                ],
            ],
            [
                'name' => 'reschedule_appointment',
                'description' => 'Remarca um agendamento existente do cliente pra um novo horário, depois que ele confirmar.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'appointment_id' => ['type' => 'INTEGER', 'description' => 'ID do agendamento a remarcar'],
                        'new_start_at' => ['type' => 'STRING', 'description' => 'Nova data e hora de início no formato YYYY-MM-DD HH:mm'],
                    ],
                    'required' => ['appointment_id', 'new_start_at'],
                ],
            ],
            [
                'name' => 'search_knowledge_base',
                'description' => 'Busca na base de conhecimento do estabelecimento uma resposta pra uma pergunta fora do fluxo de agendamento.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'query' => ['type' => 'STRING', 'description' => 'A pergunta do cliente'],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function executeTool(User $client, string $name, array $args, Business $business): array
    {
        return match ($name) {
            'list_services' => $this->listServices($business),
            'check_available_slots' => $this->checkAvailableSlots($args, $business),
            'book_appointment' => $this->createBooking($client, $args, $business),
            'list_my_appointments' => $this->listMyAppointments($client),
            'cancel_appointment' => $this->cancelAppointment($client, $args),
            'reschedule_appointment' => $this->rescheduleAppointment($client, $args),
            'search_knowledge_base' => $this->searchKnowledgeBase($args, $business),
            default => ['error' => "Ferramenta desconhecida: {$name}"],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function listServices(Business $business): array
    {
        /** @var Collection<int, Service> $services */
        $services = Service::query()
            ->where('business_id', $business->id)
            ->where('active', true)
            ->with('staff:id,name')
            ->get(['id', 'name', 'duration_minutes', 'price']);

        return ['services' => $services->map(fn (Service $service) => [
            'id' => $service->id,
            'name' => $service->name,
            'duration_minutes' => $service->duration_minutes,
            'price' => (float) $service->price,
            'staff' => $service->staff->map(fn (User $staff) => [
                'id' => $staff->id,
                'name' => $staff->name,
            ])->all(),
        ])->all()];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function findService(array $args, Business $business): ?Service
    {
        if (! isset($args['service_id']) || ! is_numeric($args['service_id'])) {
            return null;
        }

        return Service::query()->where('business_id', $business->id)->find((int) $args['service_id']);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function findStaff(array $args): ?User
    {
        if (! isset($args['staff_id']) || ! is_numeric($args['staff_id'])) {
            return null;
        }

        return User::find((int) $args['staff_id']);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function checkAvailableSlots(array $args, Business $business): array
    {
        $service = $this->findService($args, $business);

        if (! $service) {
            return ['error' => 'Serviço não encontrado.'];
        }

        try {
            $slots = $this->bookingService->availableSlots($service, (string) $args['date'], $this->findStaff($args));
        } catch (InvalidStaffAssignmentException $exception) {
            return ['error' => $exception->getMessage()];
        }

        return ['slots' => array_map(fn (Carbon $slot) => $slot->format('Y-m-d H:i'), $slots)];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function createBooking(User $client, array $args, Business $business): array
    {
        $service = $this->findService($args, $business);

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
                staff: $this->findStaff($args),
            );
        } catch (AppointmentConflictException|InvalidStaffAssignmentException $exception) {
            return ['error' => $exception->getMessage()];
        }

        return [
            'success' => true,
            'appointment_id' => $appointment->id,
            'start_at' => $appointment->start_at->format('Y-m-d H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listMyAppointments(User $client): array
    {
        $appointments = $client->appointments()
            ->with(['service:id,name', 'staff:id,name'])
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->get(['id', 'service_id', 'staff_id', 'start_at', 'status']);

        return ['appointments' => $appointments->map(fn (Appointment $appointment) => [
            'appointment_id' => $appointment->id,
            'service' => $appointment->service->name,
            'staff' => $appointment->staff?->name,
            'start_at' => $appointment->start_at->format('Y-m-d H:i'),
            'status' => $appointment->status->value,
        ])->all()];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function findClientAppointment(User $client, array $args): ?Appointment
    {
        if (! isset($args['appointment_id']) || ! is_numeric($args['appointment_id'])) {
            return null;
        }

        return $client->appointments()->find((int) $args['appointment_id']);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function cancelAppointment(User $client, array $args): array
    {
        $appointment = $this->findClientAppointment($client, $args);

        if (! $appointment) {
            return ['error' => 'Agendamento não encontrado.'];
        }

        try {
            $this->bookingService->cancelByClient($appointment);
        } catch (AppointmentActionNotAllowedException $exception) {
            return ['error' => $exception->getMessage()];
        }

        $appointment->refresh();
        $this->notifier->notifyCancelled($appointment);

        return ['success' => true, 'appointment_id' => $appointment->id];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function rescheduleAppointment(User $client, array $args): array
    {
        $appointment = $this->findClientAppointment($client, $args);

        if (! $appointment) {
            return ['error' => 'Agendamento não encontrado.'];
        }

        try {
            $rescheduled = $this->bookingService->reschedule(
                $appointment,
                Carbon::parse((string) ($args['new_start_at'] ?? ''))
            );
        } catch (AppointmentActionNotAllowedException|AppointmentConflictException $exception) {
            return ['error' => $exception->getMessage()];
        }

        $this->notifier->notifyRescheduled($rescheduled);

        return [
            'success' => true,
            'appointment_id' => $rescheduled->id,
            'start_at' => $rescheduled->start_at->format('Y-m-d H:i'),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function searchKnowledgeBase(array $args, Business $business): array
    {
        $query = isset($args['query']) ? (string) $args['query'] : '';

        if ($query === '') {
            return ['results' => []];
        }

        $faqs = $this->embeddingService->searchFaqs($business, $query);

        return ['results' => $faqs->map(fn ($faq) => [
            'question' => $faq->question,
            'answer' => $faq->answer,
        ])->all()];
    }
}
