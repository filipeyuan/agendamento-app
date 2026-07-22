<?php

declare(strict_types=1);

namespace Tests\Feature\GoogleCalendar;

use App\Enums\UserRole;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleCalendarConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_get_the_connect_url(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->getJson('/api/admin/google-calendar/connect');

        $response->assertOk();
        $this->assertStringContainsString('accounts.google.com', $response->json('url'));
    }

    #[Test]
    public function client_cannot_get_the_connect_url(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->getJson('/api/admin/google-calendar/connect');

        $response->assertForbidden();
    }

    #[Test]
    public function callback_stores_the_connection_with_a_valid_state(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Cache::put('google-calendar-oauth-state:test-state', $admin->id, now()->addMinutes(10));

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
            ]),
        ]);

        $response = $this->get('/api/google-calendar/callback?code=auth-code&state=test-state');

        $response->assertRedirect();
        $this->assertStringContainsString('google=connected', $response->headers->get('Location'));
        $this->assertDatabaseHas('google_calendar_connections', ['connected_by' => $admin->id]);
    }

    #[Test]
    public function callback_redirects_with_error_on_invalid_state(): void
    {
        $response = $this->get('/api/google-calendar/callback?code=auth-code&state=invalid-state');

        $response->assertRedirect();
        $this->assertStringContainsString('google=error', $response->headers->get('Location'));
        $this->assertDatabaseCount('google_calendar_connections', 0);
    }

    #[Test]
    public function admin_can_check_connection_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->getJson('/api/admin/google-calendar/status');
        $response->assertOk();
        $response->assertJsonPath('connected', false);

        GoogleCalendarConnection::factory()->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/google-calendar/status');
        $response->assertOk();
        $response->assertJsonPath('connected', true);
    }

    #[Test]
    public function admin_can_disconnect(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        GoogleCalendarConnection::factory()->create();

        $response = $this->actingAs($admin)->deleteJson('/api/admin/google-calendar/disconnect');

        $response->assertNoContent();
        $this->assertDatabaseCount('google_calendar_connections', 0);
    }
}
