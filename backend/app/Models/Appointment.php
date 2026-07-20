<?php

namespace App\Models;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'service_id', 'confirmed_by', 'start_at', 'end_at', 'status', 'source', 'notes'])]
class Appointment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'source' => AppointmentSource::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * Agendamentos que ocupam um horário (ignora os cancelados).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', AppointmentStatus::Cancelled);
    }

    /**
     * Agendamentos ativos cujo intervalo cruza com [$startAt, $endAt).
     */
    public function scopeOverlapping(Builder $query, Carbon $startAt, Carbon $endAt): Builder
    {
        return $query->active()
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt);
    }
}
