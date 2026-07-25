<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PaymentSetupFailedException;
use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeService
{
    private const API = 'https://api.stripe.com/v1';

    /**
     * Cria uma sessão de checkout do Stripe pro agendamento e retorna o id da sessão + a url de pagamento.
     *
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(Appointment $appointment): array
    {
        $frontendUrl = $this->frontendUrl();

        $response = Http::asForm()->withToken((string) config('services.stripe.secret'))->post(
            self::API.'/checkout/sessions',
            [
                'mode' => 'payment',
                'success_url' => "{$frontendUrl}/meus-agendamentos?payment=success",
                'cancel_url' => "{$frontendUrl}/agendar?payment=cancelled",
                'customer_email' => $appointment->user->email,
                'expires_at' => now()->addMinutes(30)->timestamp,
                'metadata' => [
                    'appointment_id' => (string) $appointment->id,
                ],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'brl',
                        'unit_amount' => (int) round(((float) $appointment->service->price) * 100),
                        'product_data' => [
                            'name' => $appointment->service->name,
                        ],
                    ],
                ]],
            ]
        );

        if ($response->failed()) {
            Log::error('Falha ao criar sessão de pagamento no Stripe.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new PaymentSetupFailedException;
        }

        return [
            'id' => (string) $response->json('id'),
            'url' => (string) $response->json('url'),
        ];
    }

    /**
     * Valida a assinatura do webhook (HMAC com o segredo do endpoint) e retorna o payload decodificado.
     * Retorna null se a assinatura for inválida ou estiver ausente.
     *
     * @return array<string, mixed>|null
     */
    public function verifyWebhook(string $payload, string $signatureHeader): ?array
    {
        $parts = [];

        foreach (explode(',', $signatureHeader) as $chunk) {
            $segments = explode('=', $chunk, 2);

            if (count($segments) === 2) {
                $parts[$segments[0]] = $segments[1];
            }
        }

        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;

        if (! $timestamp || ! $signature) {
            return null;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", (string) config('services.stripe.webhook_secret'));

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($payload, true);

        return $decoded;
    }

    private function frontendUrl(): string
    {
        /** @var array<int, string> $allowedOrigins */
        $allowedOrigins = config('cors.allowed_origins');

        return $allowedOrigins[0] ?? 'http://localhost:3000';
    }
}
