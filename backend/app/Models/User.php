<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Throwable;

/**
 * @property UserRole $role
 * @property int|null $business_id
 */
#[Fillable(['name', 'email', 'password', 'phone', 'business_id', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function sendEmailVerification(): void
    {
        $token = Str::random(40);
        $this->forceFill(['email_verification_token' => $token])->save();

        /** @var array<int, string> $allowedOrigins */
        $allowedOrigins = config('cors.allowed_origins');
        $frontendUrl = rtrim($allowedOrigins[0] ?? 'http://localhost:3000', '/');
        $url = "{$frontendUrl}/verificar-email?token={$token}";

        try {
            Mail::to($this->email)->send(new EmailVerificationMail($url));
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar e-mail de verificação.', [
                'user_id' => $this->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function sendPasswordResetNotification($token): void
    {
        /** @var array<int, string> $allowedOrigins */
        $allowedOrigins = config('cors.allowed_origins');
        $frontendUrl = rtrim($allowedOrigins[0] ?? 'http://localhost:3000', '/');
        $url = "{$frontendUrl}/redefinir-senha?token={$token}&email=".urlencode($this->email);

        try {
            Mail::to($this->email)->send(new PasswordResetMail($url));
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar e-mail de redefinição de senha.', [
                'user_id' => $this->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function createdServices(): HasMany
    {
        return $this->hasMany(Service::class, 'created_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'deactivated_at' => 'datetime',
        ];
    }
}
