<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_a_new_user_as_client(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.email', 'maria@example.com');
        $response->assertJsonStructure(['user', 'token']);

        $user = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame(UserRole::Client, $user->role);
    }

    #[Test]
    public function it_rejects_registration_with_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'outra-senha',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
    }

    #[Test]
    public function it_rejects_registration_with_an_email_already_in_use(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    #[Test]
    public function it_never_lets_a_client_self_promote_to_admin_via_mass_assignment(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::Admin->value,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.role', UserRole::Client->value);
    }
}
