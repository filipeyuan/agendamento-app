<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_logs_in_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'maria@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'maria@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'token']);
    }

    #[Test]
    public function it_rejects_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'maria@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'maria@example.com',
            'password' => 'senha-errada',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    #[Test]
    public function it_rejects_login_for_an_email_that_does_not_exist(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'ninguem@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    #[Test]
    public function authenticated_user_can_fetch_their_own_data(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonPath('data.id', $user->id);
    }

    #[Test]
    public function logout_revokes_the_current_access_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/logout');

        $response->assertOk();
        $this->assertSame(0, $user->tokens()->count());
    }
}
