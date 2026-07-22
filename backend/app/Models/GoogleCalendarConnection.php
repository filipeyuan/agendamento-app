<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GoogleCalendarConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $access_token
 * @property string $refresh_token
 * @property Carbon $expires_at
 * @property string $calendar_id
 */
#[Fillable(['access_token', 'refresh_token', 'expires_at', 'calendar_id', 'connected_by'])]
class GoogleCalendarConnection extends Model
{
    /** @use HasFactory<GoogleCalendarConnectionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
