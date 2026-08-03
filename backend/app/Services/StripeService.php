<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Exceptions\PaymentSetupFailedException;
use App\Models\Appointment;
use App\Models\Business;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeService
{
    private const API = 'https://api.stripe.com/v1';

    private const PRO_PLAN_PRICE_CENTS = 2990;

    /**
     * Cria uma sessão de checkout do Stripe pro agendamento e retorna o id da sessão + a url de pagamento.
     *
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(Appointment $appointment): array
    {
        return $this->createSession(
            pricingAppointment: $appointment,
            quantity: 1,
            metadata: ['appointment_id' => (string) $appointment->id],
        );
    }

    /**
     * Cria uma única sessão de checkout cobrindo todas as ocorrências de um agendamento
     * recorrente (mesmo serviço, quantidade = número de ocorrências).
     *
     * @param  Collection<int, Appointment>  $appointments
     * @return array{id: string, url: string}
     */
    public function createRecurringCheckoutSession(Collection $appointments): array
    {
        /** @var Appointment $first */
        $first = $appointments->first();

        return $this->createSession(
            pricingAppointment: $first,
            quantity: $appointments->count(),
            metadata: ['recurring_group_id' => (string) $first->recurring_group_id],
        );
    }

    /**
     * @param  array<string, string>  $metadata
     * @return array{id: string, url: string}
     */
    private function createSession(Appointment $pricingAppointment, int $quantity, array $metadata): array
    {
        $frontendUrl = $this->frontendUrl();

        $response = Http::asForm()->withToken((string) config('services.stripe.secret'))->post(
            self::API.'/checkout/sessions',
            [
                'mode' => 'payment',
                'success_url' => "{$frontendUrl}/meus-agendamentos?payment=success",
                'cancel_url' => "{$frontendUrl}/agendar?payment=cancelled",
                'customer_email' => $pricingAppointment->user->email,
                'expires_at' => now()->addMinutes(30)->timestamp,
                'metadata' => $metadata,
                'line_items' => [[
                    'quantity' => $quantity,
                    'price_data' => [
                        'currency' => 'brl',
                        'unit_amount' => (int) round(((float) $pricingAppointment->service->price) * 100),
                        'product_data' => [
                            'name' => $pricingAppointment->service->name,
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
     * Reembolsa (total ou parcialmente, via PaymentIntent) o valor do serviço desse agendamento
     * específico. Não faz nada se ele não estiver pago ou não tiver payment_intent registrado
     * (ex: agendamentos criados antes dessa coluna existir). Nunca lança exceção: uma falha no
     * reembolso não pode impedir o cancelamento em si, só fica registrada no log pra ação manual.
     */
    public function refund(Appointment $appointment): void
    {
        if ($appointment->payment_status !== PaymentStatus::Paid || ! $appointment->stripe_payment_intent_id) {
            return;
        }

        $response = Http::asForm()->withToken((string) config('services.stripe.secret'))->post(
            self::API.'/refunds',
            [
                'payment_intent' => $appointment->stripe_payment_intent_id,
                'amount' => (int) round(((float) $appointment->service->price) * 100),
            ]
        );

        if ($response->failed()) {
            Log::error('Falha ao reembolsar pagamento no Stripe.', [
                'appointment_id' => $appointment->id,
                'payment_intent' => $appointment->stripe_payment_intent_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return;
        }

        $appointment->update(['payment_status' => PaymentStatus::Refunded]);
    }

    /**
     * @return array{id: string, url: string}
     */
    public function createSubscriptionCheckoutSession(Business $business, string $adminEmail): array
    {
        $frontendUrl = $this->frontendUrl();

        $response = Http::asForm()->withToken((string) config('services.stripe.secret'))->post(
            self::API.'/checkout/sessions',
            [
                'mode' => 'subscription',
                'success_url' => "{$frontendUrl}/admin/plano?upgrade=success",
                'cancel_url' => "{$frontendUrl}/admin/plano?upgrade=cancelled",
                'customer_email' => $adminEmail,
                'metadata' => ['business_id' => (string) $business->id],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'brl',
                        'unit_amount' => self::PRO_PLAN_PRICE_CENTS,
                        'recurring' => ['interval' => 'month'],
                        'product_data' => [
                            'name' => 'Zelo Pro',
                        ],
                    ],
                ]],
            ]
        );

        if ($response->failed()) {
            Log::error('Falha ao criar sessão de assinatura no Stripe.', [
                'business_id' => $business->id,
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
     * @return array{url: string}
     */
    public function createBillingPortalSession(Business $business): array
    {
        $response = Http::asForm()->withToken((string) config('services.stripe.secret'))->post(
            self::API.'/billing_portal/sessions',
            [
                'customer' => $business->stripe_customer_id,
                'return_url' => "{$this->frontendUrl()}/admin/plano",
            ]
        );

        if ($response->failed()) {
            Log::error('Falha ao criar sessão do portal de cobrança no Stripe.', [
                'business_id' => $business->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new PaymentSetupFailedException;
        }

        return ['url' => (string) $response->json('url')];
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
