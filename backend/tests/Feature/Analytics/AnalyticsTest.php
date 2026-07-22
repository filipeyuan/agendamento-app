<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_view_the_analytics_summary(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create(['name' => 'Corte de cabelo', 'price' => 40]);

        Appointment::factory()->create([
            'service_id' => $service->id,
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDays(2)->addMinutes(30),
        ]);
        Appointment::factory()->create([
            'service_id' => $service->id,
            'status' => AppointmentStatus::Completed,
            'start_at' => now()->subDay(),
            'end_at' => now()->subDay()->addMinutes(30),
        ]);
        Appointment::factory()->create([
            'service_id' => $service->id,
            'status' => AppointmentStatus::Cancelled,
            'start_at' => now()->subDay(),
            'end_at' => now()->subDay()->addMinutes(30),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/analytics?days=7');

        $response->assertOk();
        $response->assertJsonPath('by_status.confirmed', 1);
        $response->assertJsonPath('by_status.completed', 1);
        $response->assertJsonPath('by_status.cancelled', 1);
        $response->assertJsonPath('by_status.pending', 0);
        $response->assertJsonPath('revenue', 80);
        $response->assertJsonCount(7, 'by_day');
        $response->assertJsonPath('top_services.0.name', 'Corte de cabelo');
        $response->assertJsonPath('top_services.0.count', 3);
    }

    #[Test]
    public function client_cannot_view_the_analytics_summary(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->getJson('/api/admin/analytics');

        $response->assertForbidden();
    }

    #[Test]
    public function appointments_outside_the_range_are_not_counted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create();

        Appointment::factory()->create([
            'service_id' => $service->id,
            'status' => AppointmentStatus::Completed,
            'start_at' => now()->subDays(40),
            'end_at' => now()->subDays(40)->addMinutes(30),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/analytics?days=7');

        $response->assertOk();
        $response->assertJsonPath('by_status.completed', 0);
        $response->assertJsonPath('revenue', 0);
    }
}
