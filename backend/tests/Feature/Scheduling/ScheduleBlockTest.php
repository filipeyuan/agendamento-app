<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Enums\UserRole;
use App\Models\ScheduleBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScheduleBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_create_an_all_day_block(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->postJson('/api/admin/schedule-blocks', [
            'date' => now()->addWeek()->toDateString(),
            'reason' => 'Feriado',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.start_time', null);
        $response->assertJsonPath('data.reason', 'Feriado');
    }

    #[Test]
    public function admin_can_create_a_partial_block(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->postJson('/api/admin/schedule-blocks', [
            'date' => now()->addWeek()->toDateString(),
            'start_time' => '12:00',
            'end_time' => '13:00',
            'reason' => 'Almoço',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.start_time', '12:00');
        $response->assertJsonPath('data.end_time', '13:00');
    }

    #[Test]
    public function client_cannot_create_a_block(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->postJson('/api/admin/schedule-blocks', [
            'date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_list_blocks_in_a_period(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ScheduleBlock::factory()->create(['date' => '2026-08-01']);
        ScheduleBlock::factory()->create(['date' => '2026-09-01']);

        $response = $this->actingAs($admin)->getJson('/api/admin/schedule-blocks?from=2026-07-01&to=2026-08-31');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.date', '2026-08-01');
    }

    #[Test]
    public function admin_can_delete_a_block(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $block = ScheduleBlock::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/admin/schedule-blocks/{$block->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('schedule_blocks', ['id' => $block->id]);
    }

    #[Test]
    public function client_cannot_delete_a_block(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);
        $block = ScheduleBlock::factory()->create();

        $response = $this->actingAs($client)->deleteJson("/api/admin/schedule-blocks/{$block->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('schedule_blocks', ['id' => $block->id]);
    }
}
