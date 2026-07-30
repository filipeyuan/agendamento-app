<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Mail\PaymentConfirmedMail;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: string}
     */
    private function signedPayload(array $payload): array
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $json = (string) json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$json}", 'whsec_test_secret');

        return [$json, "t={$timestamp},v1={$signature}"];
    }

    #[Test]
    public function checkout_completed_marks_the_appointment_as_paid_and_sends_an_email(): void
    {
        Mail::fake();

        $appointment = Appointment::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'stripe_checkout_session_id' => 'cs_test_123',
        ]);

        [$payload, $signature] = $this->signedPayload([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_123']],
        ]);

        $response = $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'payment_status' => 'paid',
        ]);
        Mail::assertSent(PaymentConfirmedMail::class, fn ($mail) => $mail->appointment->id === $appointment->id);
    }

    #[Test]
    public function checkout_completed_stores_the_payment_intent_id(): void
    {
        Mail::fake();

        $appointment = Appointment::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'stripe_checkout_session_id' => 'cs_test_789',
        ]);

        [$payload, $signature] = $this->signedPayload([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_789', 'payment_intent' => 'pi_test_789']],
        ]);

        $response = $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'stripe_payment_intent_id' => 'pi_test_789',
        ]);
    }

    #[Test]
    public function checkout_expired_deletes_the_still_unpaid_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'stripe_checkout_session_id' => 'cs_test_456',
        ]);

        [$payload, $signature] = $this->signedPayload([
            'type' => 'checkout.session.expired',
            'data' => ['object' => ['id' => 'cs_test_456']],
        ]);

        $response = $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    #[Test]
    public function it_rejects_a_webhook_with_an_invalid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $payload = (string) json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_123']],
        ]);

        $response = $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'HTTP_Stripe-Signature' => 't='.time().',v1=invalid',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(400);
    }
}
