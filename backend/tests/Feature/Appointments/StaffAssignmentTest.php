<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();

        foreach (range(0, 6) as $dayOfWeek) {
            BusinessHour::factory()->create(['business_id' => $this->business->id, 'day_of_week' => $dayOfWeek]);
        }
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'business_id' => $this->business->id]);
    }

    #[Test]
    public function a_service_without_staff_behaves_exactly_like_before(): void
    {
        $service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 30]);
        $date = now()->addDay()->toDateString();

        $response = $this->getJson("/api/services/{$service->id}/available-slots?date={$date}");

        $response->assertOk();
        $this->assertNotEmpty($response->json('slots'));
    }

    #[Test]
    public function checking_slots_for_a_staffed_service_without_choosing_staff_is_rejected(): void
    {
        $service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 30]);
        $service->staff()->attach($this->admin());
        $date = now()->addDay()->toDateString();

        $response = $this->getJson("/api/services/{$service->id}/available-slots?date={$date}");

        $response->assertUnprocessable();
    }

    #[Test]
    public function checking_slots_with_a_staff_member_not_assigned_to_the_service_is_rejected(): void
    {
        $service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 30]);
        $service->staff()->attach($this->admin());
        $outsider = User::factory()->admin()->create();
        $date = now()->addDay()->toDateString();

        $response = $this->getJson("/api/services/{$service->id}/available-slots?date={$date}&staff_id={$outsider->id}");

        $response->assertUnprocessable();
    }

    #[Test]
    public function one_staff_member_being_busy_does_not_block_another_staff_members_availability(): void
    {
        $ana = $this->admin();
        $bruno = $this->admin();
        $service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 30]);
        $service->staff()->attach([$ana->id, $bruno->id]);
        $date = now()->addDay()->toDateString();

        Appointment::factory()->create([
            'service_id' => $service->id,
            'staff_id' => $ana->id,
            'business_id' => $this->business->id,
            'start_at' => "{$date} 10:00:00",
            'end_at' => "{$date} 10:30:00",
        ]);

        $anaSlots = $this->getJson("/api/services/{$service->id}/available-slots?date={$date}&staff_id={$ana->id}")->json('slots');
        $brunoSlots = $this->getJson("/api/services/{$service->id}/available-slots?date={$date}&staff_id={$bruno->id}")->json('slots');

        $tenAm = now()->parse("{$date} 10:00:00")->toIso8601String();
        $this->assertNotContains($tenAm, $anaSlots);
        $this->assertContains($tenAm, $brunoSlots);
    }

    #[Test]
    public function client_can_book_a_staffed_service_choosing_a_professional(): void
    {
        Http::fake([
            'api.stripe.com/*' => Http::response(['id' => 'cs_test_staff', 'url' => 'https://checkout.stripe.com/pay/cs_test_staff']),
        ]);

        $ana = $this->admin();
        $service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 30]);
        $service->staff()->attach($ana);
        $client = User::factory()->create();
        $startAt = now()->addDay()->setTime(10, 0);

        $response = $this->actingAs($client)->postJson('/api/appointments', [
            'service_id' => $service->id,
            'staff_id' => $ana->id,
            'start_at' => $startAt->toIso8601String(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.staff.id', $ana->id);
        $this->assertDatabaseHas('appointments', ['service_id' => $service->id, 'staff_id' => $ana->id]);
    }

    #[Test]
    public function booking_a_staffed_service_without_choosing_staff_is_rejected(): void
    {
        $ana = $this->admin();
        $service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 30]);
        $service->staff()->attach($ana);
        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/appointments', [
            'service_id' => $service->id,
            'start_at' => now()->addDay()->setTime(10, 0)->toIso8601String(),
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('appointments', ['service_id' => $service->id]);
    }

    #[Test]
    public function admin_can_assign_staff_when_creating_a_service(): void
    {
        $admin = $this->admin();
        $colleague = $this->admin();

        $response = $this->actingAs($admin)->postJson('/api/admin/services', [
            'name' => 'Corte',
            'duration_minutes' => 30,
            'price' => 50,
            'staff_ids' => [$admin->id, $colleague->id],
        ]);

        $response->assertCreated();
        $service = Service::find($response->json('data.id'));
        $this->assertSame([$admin->id, $colleague->id], $service->staff()->pluck('users.id')->sort()->values()->all());
    }

    #[Test]
    public function admin_cannot_assign_a_user_from_another_business_as_staff(): void
    {
        $admin = $this->admin();
        $outsider = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/services', [
            'name' => 'Corte',
            'duration_minutes' => 30,
            'price' => 50,
            'staff_ids' => [$outsider->id],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('staff_ids.0');
    }

    #[Test]
    public function updating_a_services_staff_ids_replaces_the_previous_set(): void
    {
        $admin = $this->admin();
        $colleague = $this->admin();
        $service = Service::factory()->create(['business_id' => $this->business->id]);
        $service->staff()->attach($admin);

        $response = $this->actingAs($admin)->putJson("/api/admin/services/{$service->id}", [
            'name' => $service->name,
            'duration_minutes' => $service->duration_minutes,
            'price' => $service->price,
            'staff_ids' => [$colleague->id],
        ]);

        $response->assertOk();
        $this->assertSame([$colleague->id], $service->staff()->pluck('users.id')->all());
    }

    #[Test]
    public function removing_a_team_member_detaches_their_assigned_services(): void
    {
        $admin = $this->admin();
        $member = $this->admin();
        $service = Service::factory()->create(['business_id' => $this->business->id]);
        $service->staff()->attach($member);

        $this->actingAs($admin)->deleteJson("/api/admin/team/members/{$member->id}")->assertOk();

        $this->assertDatabaseMissing('service_user', ['service_id' => $service->id, 'user_id' => $member->id]);
    }

    #[Test]
    public function admin_can_filter_appointments_by_staff(): void
    {
        $ana = $this->admin();
        $bruno = $this->admin();
        $service = Service::factory()->create(['business_id' => $this->business->id]);

        Appointment::factory()->create(['business_id' => $this->business->id, 'service_id' => $service->id, 'staff_id' => $ana->id]);
        Appointment::factory()->create(['business_id' => $this->business->id, 'service_id' => $service->id, 'staff_id' => $bruno->id]);

        $response = $this->actingAs($ana)->getJson("/api/admin/appointments?staff_id={$ana->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.staff.id', $ana->id);
    }
}
