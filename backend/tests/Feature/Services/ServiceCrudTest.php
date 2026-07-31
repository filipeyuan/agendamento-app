<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function anyone_can_list_active_services_of_a_business(): void
    {
        $business = Business::factory()->create();
        Service::factory()->create(['business_id' => $business->id, 'name' => 'Corte de cabelo', 'active' => true]);
        Service::factory()->create(['business_id' => $business->id, 'name' => 'Serviço inativo', 'active' => false]);
        Service::factory()->create(['name' => 'Serviço de outro negócio', 'active' => true]);

        $response = $this->getJson("/api/services?business={$business->slug}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Corte de cabelo');
    }

    #[Test]
    public function listing_services_without_a_business_is_rejected(): void
    {
        $response = $this->getJson('/api/services');

        $response->assertUnprocessable();
    }

    #[Test]
    public function admin_can_create_a_service(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/services', [
            'name' => 'Barba',
            'description' => 'Aparar e alinhar a barba',
            'duration_minutes' => 30,
            'price' => 45,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Barba');
        $this->assertDatabaseHas('services', ['name' => 'Barba', 'created_by' => $admin->id]);
    }

    #[Test]
    public function client_cannot_create_a_service(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->postJson('/api/admin/services', [
            'name' => 'Barba',
            'duration_minutes' => 30,
            'price' => 45,
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_update_a_service(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['business_id' => $admin->business_id, 'price' => 50]);

        $response = $this->actingAs($admin)->putJson("/api/admin/services/{$service->id}", [
            'name' => $service->name,
            'duration_minutes' => $service->duration_minutes,
            'price' => 75,
        ]);

        $response->assertOk();
        $this->assertEquals(75, $response->json('data.price'));
    }

    #[Test]
    public function admin_can_delete_a_service(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['business_id' => $admin->business_id]);

        $response = $this->actingAs($admin)->deleteJson("/api/admin/services/{$service->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    #[Test]
    public function client_cannot_delete_a_service(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);
        $service = Service::factory()->create();

        $response = $this->actingAs($client)->deleteJson("/api/admin/services/{$service->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }
}
