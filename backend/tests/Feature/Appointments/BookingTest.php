<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authenticated_client_can_book_an_available_slot(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $startAt = now()->addDay()->setTime(10, 0);

        $response = $this->actingAs($client)->postJson('/api/appointments', [
            'service_id' => $service->id,
            'start_at' => $startAt->toIso8601String(),
            'notes' => 'Primeira vez',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', AppointmentStatus::Pending->value);
        $this->assertDatabaseHas('appointments', [
            'user_id' => $client->id,
            'service_id' => $service->id,
        ]);
    }

    #[Test]
    public function booking_a_slot_that_conflicts_returns_409(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $startAt = now()->addDay()->setTime(10, 0);

        Appointment::factory()->create([
            'service_id' => $service->id,
            'start_at' => $startAt,
            'end_at' => $startAt->clone()->addMinutes(30),
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/appointments', [
            'service_id' => $service->id,
            'start_at' => $startAt->toIso8601String(),
        ]);

        $response->assertStatus(409);
    }

    #[Test]
    public function guests_cannot_book_an_appointment(): void
    {
        $service = Service::factory()->create();

        $response = $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'start_at' => now()->addDay()->toIso8601String(),
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function a_client_only_sees_their_own_appointments(): void
    {
        $client = User::factory()->create();
        $otherClient = User::factory()->create();

        Appointment::factory()->create(['user_id' => $client->id]);
        Appointment::factory()->create(['user_id' => $otherClient->id]);

        $response = $this->actingAs($client)->getJson('/api/appointments/mine');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    public function available_slots_exclude_already_booked_times(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $date = now()->addDay()->toDateString();

        Appointment::factory()->create([
            'service_id' => $service->id,
            'start_at' => "{$date} 10:00:00",
            'end_at' => "{$date} 10:30:00",
        ]);

        $response = $this->getJson("/api/services/{$service->id}/available-slots?date={$date}");

        $response->assertOk();
        $this->assertNotContains(
            now()->parse("{$date} 10:00:00")->toIso8601String(),
            $response->json('slots'),
        );
    }
}
