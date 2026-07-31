<?php

declare(strict_types=1);

namespace Tests\Feature\MultiTenant;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Prova que um negócio não enxerga nem consegue mexer em nada de outro negócio,
 * mesmo com um token de admin válido. Complementa os testes de cada feature
 * individual (que já cobrem o caminho "feliz" dentro do próprio negócio).
 */
class CrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_cannot_update_a_service_from_another_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $admin = User::factory()->admin()->create(['business_id' => $ownBusiness->id]);

        $otherBusiness = Business::factory()->create();
        $otherService = Service::factory()->create(['business_id' => $otherBusiness->id, 'price' => 50]);

        $response = $this->actingAs($admin)->putJson("/api/admin/services/{$otherService->id}", [
            'name' => $otherService->name,
            'duration_minutes' => $otherService->duration_minutes,
            'price' => 999,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('services', ['id' => $otherService->id, 'price' => 50]);
    }

    #[Test]
    public function admin_cannot_delete_a_service_from_another_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $admin = User::factory()->admin()->create(['business_id' => $ownBusiness->id]);

        $otherBusiness = Business::factory()->create();
        $otherService = Service::factory()->create(['business_id' => $otherBusiness->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/admin/services/{$otherService->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('services', ['id' => $otherService->id]);
    }

    #[Test]
    public function admin_cannot_update_the_status_of_an_appointment_from_another_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $admin = User::factory()->admin()->create(['business_id' => $ownBusiness->id]);

        $otherBusiness = Business::factory()->create();
        $otherService = Service::factory()->create(['business_id' => $otherBusiness->id]);
        $otherAppointment = Appointment::factory()->create([
            'service_id' => $otherService->id,
            'status' => AppointmentStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/admin/appointments/{$otherAppointment->id}/status", [
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('appointments', ['id' => $otherAppointment->id, 'status' => AppointmentStatus::Pending->value]);
    }

    #[Test]
    public function admin_index_only_lists_appointments_from_their_own_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $admin = User::factory()->admin()->create(['business_id' => $ownBusiness->id]);
        $ownService = Service::factory()->create(['business_id' => $ownBusiness->id]);
        Appointment::factory()->create(['service_id' => $ownService->id]);

        $otherBusiness = Business::factory()->create();
        $otherService = Service::factory()->create(['business_id' => $otherBusiness->id]);
        Appointment::factory()->create(['service_id' => $otherService->id]);

        $response = $this->actingAs($admin)->getJson('/api/admin/appointments');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    public function admin_analytics_only_counts_their_own_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $admin = User::factory()->admin()->create(['business_id' => $ownBusiness->id]);
        $ownService = Service::factory()->create(['business_id' => $ownBusiness->id, 'price' => 40]);
        Appointment::factory()->create([
            'service_id' => $ownService->id,
            'status' => AppointmentStatus::Completed,
            'start_at' => now()->subDay(),
            'end_at' => now()->subDay()->addMinutes(30),
        ]);

        $otherBusiness = Business::factory()->create();
        $otherService = Service::factory()->create(['business_id' => $otherBusiness->id, 'price' => 500]);
        Appointment::factory()->create([
            'service_id' => $otherService->id,
            'status' => AppointmentStatus::Completed,
            'start_at' => now()->subDay(),
            'end_at' => now()->subDay()->addMinutes(30),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/analytics?days=7');

        $response->assertOk();
        $response->assertJsonPath('revenue', 40);
    }

    #[Test]
    public function updating_business_hours_never_touches_another_businesss_hours(): void
    {
        $ownBusiness = Business::factory()->create();
        $admin = User::factory()->admin()->create(['business_id' => $ownBusiness->id]);

        $otherBusiness = Business::factory()->create();
        BusinessHour::factory()->create(['business_id' => $otherBusiness->id, 'day_of_week' => 1, 'is_open' => true]);

        $hours = collect(range(0, 6))->map(fn (int $day) => [
            'day_of_week' => $day,
            'is_open' => false,
            'start_time' => null,
            'end_time' => null,
        ])->all();

        $response = $this->actingAs($admin)->putJson('/api/admin/business-hours', ['hours' => $hours]);

        $response->assertOk();
        $this->assertDatabaseHas('business_hours', [
            'business_id' => $otherBusiness->id,
            'day_of_week' => 1,
            'is_open' => true,
        ]);
    }

    #[Test]
    public function admin_cannot_delete_a_schedule_block_from_another_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $admin = User::factory()->admin()->create(['business_id' => $ownBusiness->id]);

        $otherBusiness = Business::factory()->create();
        $otherBlock = ScheduleBlock::factory()->create(['business_id' => $otherBusiness->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/admin/schedule-blocks/{$otherBlock->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('schedule_blocks', ['id' => $otherBlock->id]);
    }

    #[Test]
    public function schedule_blocks_index_only_lists_blocks_from_the_admins_own_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $admin = User::factory()->admin()->create(['business_id' => $ownBusiness->id]);
        ScheduleBlock::factory()->create(['business_id' => $ownBusiness->id, 'date' => '2026-08-01']);

        $otherBusiness = Business::factory()->create();
        ScheduleBlock::factory()->create(['business_id' => $otherBusiness->id, 'date' => '2026-08-01']);

        $response = $this->actingAs($admin)->getJson('/api/admin/schedule-blocks?from=2026-07-01&to=2026-08-31');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    public function client_can_book_with_two_different_businesses_and_see_both_separately(): void
    {
        foreach ([$businessA = Business::factory()->create(), $businessB = Business::factory()->create()] as $business) {
            foreach (range(0, 6) as $dayOfWeek) {
                BusinessHour::factory()->create(['business_id' => $business->id, 'day_of_week' => $dayOfWeek]);
            }
        }

        $serviceA = Service::factory()->create(['business_id' => $businessA->id]);
        $serviceB = Service::factory()->create(['business_id' => $businessB->id]);

        $client = User::factory()->create();
        Appointment::factory()->create(['user_id' => $client->id, 'service_id' => $serviceA->id]);
        Appointment::factory()->create(['user_id' => $client->id, 'service_id' => $serviceB->id]);

        $response = $this->actingAs($client)->getJson('/api/appointments/mine?scope=all');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $businessNames = collect($response->json('data'))->pluck('business.slug')->sort()->values();
        $this->assertEquals(
            collect([$businessA->slug, $businessB->slug])->sort()->values(),
            $businessNames
        );
    }

    #[Test]
    public function assistant_cannot_find_a_service_from_a_different_business(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        $businessA = Business::factory()->create();
        foreach (range(0, 6) as $dayOfWeek) {
            BusinessHour::factory()->create(['business_id' => $businessA->id, 'day_of_week' => $dayOfWeek]);
        }

        $businessB = Business::factory()->create();
        $serviceFromB = Service::factory()->create(['business_id' => $businessB->id]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => ['role' => 'model', 'parts' => [[
                            'functionCall' => ['name' => 'check_available_slots', 'args' => [
                                'service_id' => $serviceFromB->id,
                                'date' => now()->addDay()->toDateString(),
                            ]],
                        ]]],
                    ]],
                ])
                ->push([
                    'candidates' => [[
                        'content' => ['role' => 'model', 'parts' => [['text' => 'Não achei esse serviço.']]],
                    ]],
                ]),
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $businessA->slug,
            'messages' => [['role' => 'user', 'content' => 'Tem horário amanhã?']],
        ]);

        $response->assertOk();
        $this->assertEquals('Não achei esse serviço.', $response->json('message'));

        // Confirma que a ferramenta realmente devolveu "não encontrado" pro Gemini, não
        // vazou os horários do serviço do outro negócio.
        Http::assertSent(function ($request) {
            $body = $request->data();
            $lastPart = collect($body['contents'] ?? [])->last()['parts'][0] ?? [];
            $toolResponse = $lastPart['functionResponse']['response'] ?? null;

            return $toolResponse !== null && ($toolResponse['error'] ?? null) === 'Serviço não encontrado.';
        });
    }
}
