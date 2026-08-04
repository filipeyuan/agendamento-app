<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $business_id
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property AppointmentStatus $status
 * @property AppointmentSource $source
 * @property PaymentStatus $payment_status
 * @property-read User $user
 * @property-read Service $service
 */
#[Fillable([
    'user_id', 'service_id', 'staff_id', 'business_id', 'confirmed_by', 'start_at', 'end_at', 'status', 'source', 'notes',
    'google_event_id', 'payment_status', 'stripe_checkout_session_id', 'stripe_payment_intent_id', 'recurring_group_id',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Agendamentos que ocupam um horário (ignora os cancelados).
     *
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', AppointmentStatus::Cancelled);
    }

    /**
     * Agendamentos ativos cujo intervalo cruza com [$startAt, $endAt).
     *
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeOverlapping(Builder $query, Carbon $startAt, Carbon $endAt): Builder
    {
        return $query->active()
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt);
    }

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'source' => AppointmentSource::class,
            'payment_status' => PaymentStatus::class,
        ];
    }
}
