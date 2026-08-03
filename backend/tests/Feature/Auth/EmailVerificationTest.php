<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_verification_email_on_registration(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'client',
        ]);

        $response->assertCreated();
        Mail::assertSent(EmailVerificationMail::class);

        $user = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verification_token);
        $this->assertNull($user->email_verified_at);
    }

    #[Test]
    public function it_verifies_the_email_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'email_verification_token' => 'token-valido',
        ]);

        $response = $this->postJson('/api/email/verify', ['token' => 'token-valido']);

        $response->assertOk();
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_verification_token);
    }

    #[Test]
    public function it_rejects_an_invalid_verification_token(): void
    {
        $response = $this->postJson('/api/email/verify', ['token' => 'token-invalido']);

        $response->assertUnprocessable();
    }

    #[Test]
    public function an_authenticated_user_can_resend_the_verification_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/email/resend');

        $response->assertOk();
        Mail::assertSent(EmailVerificationMail::class);
    }

    #[Test]
    public function resend_is_a_no_op_when_the_email_is_already_verified(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/email/resend');

        $response->assertOk();
        Mail::assertNothingSent();
    }

    #[Test]
    public function guests_cannot_resend_the_verification_email(): void
    {
        $this->postJson('/api/email/resend')->assertUnauthorized();
    }
}
