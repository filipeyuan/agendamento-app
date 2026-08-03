<?php

declare(strict_types=1);

namespace Tests\Feature\Business;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BusinessAccentColorTest extends TestCase
{
    use RefreshDatabase;

    private function proAdmin(): User
    {
        $business = Business::factory()->pro()->create();

        return User::factory()->create(['role' => UserRole::Admin, 'business_id' => $business->id]);
    }

    #[Test]
    public function pro_admin_can_set_an_accent_color(): void
    {
        $admin = $this->proAdmin();

        $response = $this->actingAs($admin)->putJson('/api/admin/business/accent-color', [
            'accent_color' => '#16A34A',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.accent_color', '#16A34A');
        $this->assertSame('#16A34A', $admin->business->fresh()->accent_color);
    }

    #[Test]
    public function pro_admin_can_reset_the_accent_color_to_default(): void
    {
        $admin = $this->proAdmin();
        $admin->business->forceFill(['accent_color' => '#16A34A'])->save();

        $response = $this->actingAs($admin)->putJson('/api/admin/business/accent-color', [
            'accent_color' => null,
        ]);

        $response->assertOk();
        $this->assertNull($admin->business->fresh()->accent_color);
    }

    #[Test]
    public function an_invalid_color_is_rejected(): void
    {
        $admin = $this->proAdmin();

        $response = $this->actingAs($admin)->putJson('/api/admin/business/accent-color', [
            'accent_color' => 'not-a-color',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('accent_color');
    }

    #[Test]
    public function free_plan_admin_cannot_set_an_accent_color(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->putJson('/api/admin/business/accent-color', [
            'accent_color' => '#16A34A',
        ]);

        $response->assertForbidden();
        $this->assertNull($admin->business->fresh()->accent_color);
    }

    #[Test]
    public function client_cannot_set_an_accent_color(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->putJson('/api/admin/business/accent-color', [
            'accent_color' => '#16A34A',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function a_guest_cannot_set_an_accent_color(): void
    {
        $this->putJson('/api/admin/business/accent-color', ['accent_color' => '#16A34A'])
            ->assertUnauthorized();
    }
}
