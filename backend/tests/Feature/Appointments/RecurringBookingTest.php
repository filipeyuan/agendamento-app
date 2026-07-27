<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\PaymentStatus;
use App\Mail\RecurringPaymentConfirmedMail;
use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecurringBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (range(0, 6) as $dayOfWeek) {
            BusinessHour::factory()->create(['day_of_week' => $dayOfWeek]);
        }
    }

    #[Test]
    public function client_can_book_a_recurring_weekly_appointment(): void
    {
        Http::fake([
            'api.stripe.com/*' => Http::response([
                'id' => 'cs_test_recurring_123',
                'url' => 'https://checkout.stripe.com/pay/cs_test_recurring_123',
            ]),
        ]);

        $client = User::factory()->create();
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $startAt = now()->addDay()->setTime(10, 0);

        $response = $this->actingAs($client)->postJson('/api/appointments', [
            'service_id' => $service->id,
            'start_at' => $startAt->toIso8601String(),
            'recurring_occurrences' => 4,
        ]);

        $response->assertCreated();
        $response->assertJsonCount(4, 'data');
        $response->assertJsonPath('checkout_url', 'https://checkout.stripe.com/pay/cs_test_recurring_123');

        $this->assertSame(
            4,
            Appointment::query()->where('stripe_checkout_session_id', 'cs_test_recurring_123')->count()
        );

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
                && ($request['line_items'][0]['quantity'] ?? null) === 4;
        });
    }

    #[Test]
    public function a_conflicting_occurrence_rejects_the_whole_recurring_series(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $startAt = now()->addDay()->setTime(10, 0);

        // Já existe alguém agendado 3 semanas depois, no mesmo horário.
        Appointment::factory()->create([
            'service_id' => $service->id,
            'start_at' => $startAt->clone()->addWeeks(3),
            'end_at' => $startAt->clone()->addWeeks(3)->addMinutes(30),
            'payment_status' => PaymentStatus::Paid,
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/appointments', [
            'service_id' => $service->id,
            'start_at' => $startAt->toIso8601String(),
            'recurring_occurrences' => 4,
        ]);

        $response->assertStatus(409);
        $this->assertSame(
            1,
            Appointment::query()->count(),
            'só o agendamento pré-existente deveria sobrar, nenhum da série deveria ter sido criado'
        );
    }

    #[Test]
    public function paying_a_recurring_checkout_marks_every_occurrence_as_paid_and_sends_one_email(): void
    {
        Mail::fake();

        $client = User::factory()->create();
        $groupId = (string) Str::uuid();

        $appointments = Appointment::factory()->count(3)->create([
            'user_id' => $client->id,
            'payment_status' => PaymentStatus::Pending,
            'stripe_checkout_session_id' => 'cs_test_group_123',
            'recurring_group_id' => $groupId,
        ]);

        $payload = (string) json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_group_123']],
        ]);
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test_secret');

        $response = $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        foreach ($appointments as $appointment) {
            $this->assertDatabaseHas('appointments', [
                'id' => $appointment->id,
                'payment_status' => 'paid',
            ]);
        }

        Mail::assertSentCount(1);
        Mail::assertSent(RecurringPaymentConfirmedMail::class);
    }
}
