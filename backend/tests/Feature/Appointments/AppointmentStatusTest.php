<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppointmentStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_confirm_an_appointment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);

        $response = $this->actingAs($admin)->patchJson("/api/admin/appointments/{$appointment->id}/status", [
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', AppointmentStatus::Confirmed->value);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Confirmed->value,
            'confirmed_by' => $admin->id,
        ]);
    }

    #[Test]
    public function client_cannot_update_an_appointment_status(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);

        $response = $this->actingAs($client)->patchJson("/api/admin/appointments/{$appointment->id}/status", [
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function it_rejects_setting_status_back_to_pending(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Confirmed]);

        $response = $this->actingAs($admin)->patchJson("/api/admin/appointments/{$appointment->id}/status", [
            'status' => AppointmentStatus::Pending->value,
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function admin_can_filter_appointments_by_date_and_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Appointment::factory()->create([
            'status' => AppointmentStatus::Pending,
            'start_at' => '2026-08-10 10:00:00',
            'end_at' => '2026-08-10 10:30:00',
        ]);
        Appointment::factory()->create([
            'status' => AppointmentStatus::Confirmed,
            'start_at' => '2026-08-10 11:00:00',
            'end_at' => '2026-08-10 11:30:00',
        ]);
        Appointment::factory()->create([
            'status' => AppointmentStatus::Pending,
            'start_at' => '2026-08-11 09:00:00',
            'end_at' => '2026-08-11 09:30:00',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/appointments?date=2026-08-10&status=pending');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
