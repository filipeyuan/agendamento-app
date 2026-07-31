<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Business;
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
            'account_type' => 'client',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.email', 'maria@example.com');
        $response->assertJsonStructure(['user', 'token']);

        $user = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame(UserRole::Client, $user->role);
        $this->assertNull($user->business_id);
    }

    #[Test]
    public function it_registers_a_new_admin_with_a_new_business_when_account_type_is_business(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'João Barbeiro',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'business',
            'business_name' => 'Barbearia do João',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.role', UserRole::Admin->value);
        $response->assertJsonPath('user.business.name', 'Barbearia do João');
        $response->assertJsonPath('user.business.slug', 'barbearia-do-joao');

        $user = User::query()->where('email', 'joao@example.com')->firstOrFail();
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertNotNull($user->business_id);
        $this->assertDatabaseHas('businesses', ['name' => 'Barbearia do João', 'slug' => 'barbearia-do-joao']);
    }

    #[Test]
    public function it_appends_a_numeric_suffix_when_the_business_slug_already_exists(): void
    {
        Business::factory()->create(['name' => 'Barbearia do João', 'slug' => 'barbearia-do-joao']);

        $response = $this->postJson('/api/register', [
            'name' => 'João Barbeiro',
            'email' => 'joao2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'business',
            'business_name' => 'Barbearia do João',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.business.slug', 'barbearia-do-joao-2');
    }

    #[Test]
    public function it_requires_a_business_name_when_account_type_is_business(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'João Barbeiro',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'business',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('business_name');
    }

    #[Test]
    public function it_rejects_registration_with_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'outra-senha',
            'account_type' => 'client',
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
            'account_type' => 'client',
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
            'account_type' => 'client',
            'role' => UserRole::Admin->value,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.role', UserRole::Client->value);
    }
}
