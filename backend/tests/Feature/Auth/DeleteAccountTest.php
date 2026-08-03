<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_deactivate_their_account(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-atual')]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/me/deactivate', [
            'password' => 'senha-atual',
        ]);

        $response->assertOk();
        $this->assertNotNull($user->fresh()->deactivated_at);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    #[Test]
    public function it_rejects_deactivation_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-atual')]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/me/deactivate', [
            'password' => 'senha-errada',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
        $this->assertNull($user->fresh()->deactivated_at);
    }

    #[Test]
    public function it_blocks_deactivation_when_client_has_a_future_active_appointment(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-atual')]);
        $business = Business::factory()->create();
        $service = Service::factory()->for($business)->create();

        Appointment::factory()->for($user)->for($business)->for($service)->create([
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'status' => 'confirmed',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/me/deactivate', [
            'password' => 'senha-atual',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('appointments');
    }

    #[Test]
    public function it_blocks_deactivation_when_admins_business_has_a_future_active_appointment(): void
    {
        $business = Business::factory()->create();
        $admin = User::factory()->create([
            'password' => Hash::make('senha-atual'),
            'role' => UserRole::Admin,
            'business_id' => $business->id,
        ]);
        $client = User::factory()->create();
        $service = Service::factory()->for($business)->create();

        Appointment::factory()->for($client, 'user')->for($business)->for($service)->create([
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'status' => 'confirmed',
        ]);

        $token = $admin->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/me/deactivate', [
            'password' => 'senha-atual',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('appointments');
    }

    #[Test]
    public function logging_back_in_reactivates_a_deactivated_account(): void
    {
        $user = User::factory()->create([
            'email' => 'reativar@example.com',
            'password' => Hash::make('senha-atual'),
            'deactivated_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'reativar@example.com',
            'password' => 'senha-atual',
        ]);

        $response->assertOk();
        $this->assertNull($user->fresh()->deactivated_at);
    }

    #[Test]
    public function authenticated_user_can_permanently_delete_their_account(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-atual')]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/me', [
            'password' => 'senha-atual',
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[Test]
    public function it_rejects_permanent_deletion_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-atual')]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/me', [
            'password' => 'senha-errada',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    #[Test]
    public function it_blocks_permanent_deletion_when_client_has_a_future_active_appointment(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-atual')]);
        $business = Business::factory()->create();
        $service = Service::factory()->for($business)->create();

        Appointment::factory()->for($user)->for($business)->for($service)->create([
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'status' => 'pending',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/me', [
            'password' => 'senha-atual',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('appointments');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    #[Test]
    public function deleting_an_admin_account_without_future_appointments_removes_their_business(): void
    {
        $business = Business::factory()->create();
        $admin = User::factory()->create([
            'password' => Hash::make('senha-atual'),
            'role' => UserRole::Admin,
            'business_id' => $business->id,
        ]);
        $service = Service::factory()->for($business)->create();

        $token = $admin->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/me', [
            'password' => 'senha-atual',
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('businesses', ['id' => $business->id]);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    #[Test]
    public function deleting_an_admin_account_keeps_the_business_when_another_admin_remains(): void
    {
        $business = Business::factory()->create();
        $admin = User::factory()->create([
            'password' => Hash::make('senha-atual'),
            'role' => UserRole::Admin,
            'business_id' => $business->id,
        ]);
        $otherAdmin = User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);

        $token = $admin->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/me', [
            'password' => 'senha-atual',
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    #[Test]
    public function guests_cannot_deactivate_or_delete_an_account(): void
    {
        $this->patchJson('/api/me/deactivate', ['password' => 'x'])->assertUnauthorized();
        $this->deleteJson('/api/me', ['password' => 'x'])->assertUnauthorized();
    }
}
