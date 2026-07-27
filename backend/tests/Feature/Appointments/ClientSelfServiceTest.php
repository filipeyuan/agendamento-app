<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentRescheduledMail;
use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientSelfServiceTest extends TestCase
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
    public function client_can_cancel_their_own_appointment_outside_the_action_window(): void
    {
        Mail::fake();

        $client = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addMinutes(30),
        ]);

        $response = $this->actingAs($client)->patchJson("/api/appointments/{$appointment->id}/cancel");

        $response->assertOk();
        $response->assertJsonPath('data.status', AppointmentStatus::Cancelled->value);
        Mail::assertSent(AppointmentCancelledMail::class);
    }

    #[Test]
    public function client_cannot_cancel_within_the_action_window(): void
    {
        $client = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addHour(),
            'end_at' => now()->addHour()->addMinutes(30),
        ]);

        $response = $this->actingAs($client)->patchJson("/api/appointments/{$appointment->id}/cancel");

        $response->assertStatus(422);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Confirmed->value,
        ]);
    }

    #[Test]
    public function client_cannot_cancel_someone_elses_appointment(): void
    {
        $client = User::factory()->create();
        $otherClient = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $otherClient->id,
            'start_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($client)->patchJson("/api/appointments/{$appointment->id}/cancel");

        $response->assertForbidden();
    }

    #[Test]
    public function client_cannot_cancel_an_already_cancelled_appointment(): void
    {
        $client = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'status' => AppointmentStatus::Cancelled,
            'start_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($client)->patchJson("/api/appointments/{$appointment->id}/cancel");

        $response->assertStatus(422);
    }

    #[Test]
    public function client_can_reschedule_their_own_appointment_to_a_free_slot(): void
    {
        Mail::fake();

        $client = User::factory()->create();
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addDay()->setTime(10, 0),
            'end_at' => now()->addDay()->setTime(10, 30),
        ]);

        $newStart = now()->addDay()->setTime(14, 0);

        $response = $this->actingAs($client)->patchJson("/api/appointments/{$appointment->id}/reschedule", [
            'start_at' => $newStart->toIso8601String(),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'start_at' => $newStart->toDateTimeString(),
        ]);
        Mail::assertSent(AppointmentRescheduledMail::class);
    }

    #[Test]
    public function client_cannot_reschedule_to_a_slot_that_conflicts(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addDay()->setTime(10, 0),
            'end_at' => now()->addDay()->setTime(10, 30),
        ]);

        $takenStart = now()->addDay()->setTime(14, 0);
        Appointment::factory()->create([
            'service_id' => $service->id,
            'start_at' => $takenStart,
            'end_at' => $takenStart->clone()->addMinutes(30),
            'payment_status' => PaymentStatus::Paid,
        ]);

        $response = $this->actingAs($client)->patchJson("/api/appointments/{$appointment->id}/reschedule", [
            'start_at' => $takenStart->toIso8601String(),
        ]);

        $response->assertStatus(409);
    }

    #[Test]
    public function client_cannot_reschedule_within_the_action_window(): void
    {
        $client = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addHour(),
            'end_at' => now()->addHour()->addMinutes(30),
        ]);

        $response = $this->actingAs($client)->patchJson("/api/appointments/{$appointment->id}/reschedule", [
            'start_at' => now()->addDay()->toIso8601String(),
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function client_cannot_reschedule_someone_elses_appointment(): void
    {
        $client = User::factory()->create();
        $otherClient = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $otherClient->id,
            'start_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($client)->patchJson("/api/appointments/{$appointment->id}/reschedule", [
            'start_at' => now()->addDays(2)->toIso8601String(),
        ]);

        $response->assertForbidden();
    }
}
