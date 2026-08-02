<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_update_name_email_and_phone(): void
    {
        $user = User::factory()->create(['name' => 'Nome Antigo']);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/me', [
            'name' => 'Nome Novo',
            'email' => 'novo@example.com',
            'phone' => '11999998888',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Nome Novo');
        $response->assertJsonPath('data.email', 'novo@example.com');
        $this->assertSame('11999998888', $user->fresh()->phone);
    }

    #[Test]
    public function it_rejects_an_email_already_used_by_another_user(): void
    {
        User::factory()->create(['email' => 'ocupado@example.com']);
        $user = User::factory()->create(['email' => 'meu@example.com']);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/me', [
            'name' => $user->name,
            'email' => 'ocupado@example.com',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    #[Test]
    public function it_keeps_the_users_own_email_valid_on_update(): void
    {
        $user = User::factory()->create(['email' => 'meu@example.com']);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/me', [
            'name' => 'Mesmo Nome',
            'email' => 'meu@example.com',
        ]);

        $response->assertOk();
    }

    #[Test]
    public function authenticated_user_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-antiga')]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/me/password', [
            'current_password' => 'senha-antiga',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('senha-nova-123', $user->fresh()->password));
    }

    #[Test]
    public function it_rejects_password_change_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-antiga')]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/me/password', [
            'current_password' => 'senha-errada',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('current_password');
        $this->assertTrue(Hash::check('senha-antiga', $user->fresh()->password));
    }

    #[Test]
    public function it_rejects_password_change_when_confirmation_does_not_match(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-antiga')]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/me/password', [
            'current_password' => 'senha-antiga',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'nao-bate',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
    }

    #[Test]
    public function guests_cannot_update_profile_or_password(): void
    {
        $this->putJson('/api/me', ['name' => 'X', 'email' => 'x@example.com'])->assertUnauthorized();
        $this->putJson('/api/me/password', [
            'current_password' => 'a',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertUnauthorized();
    }
}
