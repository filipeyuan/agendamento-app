<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessInviteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $business_id
 * @property string $email
 * @property string $token
 * @property int $invited_by
 * @property Carbon $expires_at
 */
#[Fillable(['business_id', 'email', 'token', 'invited_by', 'expires_at'])]
class BusinessInvite extends Model
{
    /** @use HasFactory<BusinessInviteFactory> */
    use HasFactory;

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
