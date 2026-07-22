<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Enums\UserRole;
use App\Models\BusinessHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BusinessHourTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_list_business_hours(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        BusinessHour::factory()->create(['day_of_week' => 0, 'is_open' => false, 'start_time' => null, 'end_time' => null]);
        BusinessHour::factory()->create(['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '18:00']);

        $response = $this->actingAs($admin)->getJson('/api/admin/business-hours');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.day_of_week', 0);
        $response->assertJsonPath('data.0.is_open', false);
        $response->assertJsonPath('data.1.start_time', '09:00');
    }

    #[Test]
    public function client_cannot_list_business_hours(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->getJson('/api/admin/business-hours');

        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_update_business_hours(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $hours = collect(range(0, 6))->map(fn (int $day) => [
            'day_of_week' => $day,
            'is_open' => $day !== 0,
            'start_time' => $day === 0 ? null : '10:00',
            'end_time' => $day === 0 ? null : '19:00',
        ])->all();

        $response = $this->actingAs($admin)->putJson('/api/admin/business-hours', ['hours' => $hours]);

        $response->assertOk();
        $this->assertDatabaseHas('business_hours', ['day_of_week' => 0, 'is_open' => false]);
        $this->assertDatabaseHas('business_hours', ['day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '19:00']);
    }

    #[Test]
    public function client_cannot_update_business_hours(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);
        $hours = collect(range(0, 6))->map(fn (int $day) => [
            'day_of_week' => $day,
            'is_open' => true,
            'start_time' => '09:00',
            'end_time' => '18:00',
        ])->all();

        $response = $this->actingAs($client)->putJson('/api/admin/business-hours', ['hours' => $hours]);

        $response->assertForbidden();
    }
}
