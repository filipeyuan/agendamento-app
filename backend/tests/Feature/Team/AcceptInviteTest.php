<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\BusinessInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AcceptInviteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_a_preview_of_a_valid_invite(): void
    {
        $business = Business::factory()->create(['name' => 'Barbearia Zeca']);
        $invite = BusinessInvite::factory()->create(['business_id' => $business->id, 'email' => 'novo@example.com']);

        $response = $this->getJson("/api/invites/{$invite->token}");

        $response->assertOk();
        $response->assertJsonPath('email', 'novo@example.com');
        $response->assertJsonPath('business_name', 'Barbearia Zeca');
    }

    #[Test]
    public function it_rejects_an_invalid_invite_token(): void
    {
        $this->getJson('/api/invites/token-invalido')->assertUnprocessable();
    }

    #[Test]
    public function it_rejects_an_expired_invite_token(): void
    {
        $invite = BusinessInvite::factory()->create(['expires_at' => now()->subDay()]);

        $this->getJson("/api/invites/{$invite->token}")->assertUnprocessable();
    }

    #[Test]
    public function accepting_a_valid_invite_creates_an_admin_user_with_verified_email(): void
    {
        $business = Business::factory()->create();
        $invite = BusinessInvite::factory()->create(['business_id' => $business->id, 'email' => 'novo@example.com']);

        $response = $this->postJson('/api/invites/accept', [
            'token' => $invite->token,
            'name' => 'Novo Membro',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.email', 'novo@example.com');
        $response->assertJsonPath('user.role', 'admin');

        $user = User::where('email', 'novo@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame($business->id, $user->business_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseMissing('business_invites', ['id' => $invite->id]);
    }

    #[Test]
    public function it_rejects_accepting_an_expired_invite(): void
    {
        $invite = BusinessInvite::factory()->create(['expires_at' => now()->subDay()]);

        $response = $this->postJson('/api/invites/accept', [
            'token' => $invite->token,
            'name' => 'Novo Membro',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function it_rejects_accepting_an_invalid_token(): void
    {
        $response = $this->postJson('/api/invites/accept', [
            'token' => 'token-invalido',
            'name' => 'Novo Membro',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function it_rejects_accepting_an_invite_when_the_email_was_taken_in_the_meantime(): void
    {
        $invite = BusinessInvite::factory()->create(['email' => 'novo@example.com']);
        User::factory()->create(['email' => 'novo@example.com', 'role' => UserRole::Client]);

        $response = $this->postJson('/api/invites/accept', [
            'token' => $invite->token,
            'name' => 'Novo Membro',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('business_invites', ['id' => $invite->id]);
    }
}
