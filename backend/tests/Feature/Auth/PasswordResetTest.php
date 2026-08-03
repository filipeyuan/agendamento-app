<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_reset_link_when_the_email_exists(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/forgot-password', ['email' => $user->email]);

        $response->assertOk();
        Mail::assertSent(PasswordResetMail::class);
    }

    #[Test]
    public function it_returns_the_same_generic_message_when_the_email_does_not_exist(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', ['email' => 'ninguem@example.com']);

        $response->assertOk();
        Mail::assertNothingSent();
    }

    #[Test]
    public function it_resets_the_password_with_a_valid_token(): void
    {
        Mail::fake();

        $user = User::factory()->create(['password' => Hash::make('senha-antiga')]);
        $user->createToken('api');

        $this->postJson('/api/forgot-password', ['email' => $user->email]);

        $tokenRow = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->assertNotNull($tokenRow);

        Mail::assertSent(PasswordResetMail::class, function ($mail) use (&$resetUrl) {
            $resetUrl = $mail->url;

            return true;
        });

        parse_str((string) parse_url($resetUrl, PHP_URL_QUERY), $query);

        $response = $this->postJson('/api/reset-password', [
            'token' => $query['token'],
            'email' => $user->email,
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('senha-nova-123', $user->fresh()->password));
        $this->assertCount(0, $user->fresh()->tokens);
    }

    #[Test]
    public function it_rejects_an_invalid_reset_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/reset-password', [
            'token' => 'token-invalido',
            'email' => $user->email,
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ]);

        $response->assertUnprocessable();
    }
}
