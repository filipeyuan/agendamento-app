<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ScheduleBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $reason
 */
#[Fillable(['date', 'start_time', 'end_time', 'reason'])]
class ScheduleBlock extends Model
{
    /** @use HasFactory<ScheduleBlockFactory> */
    use HasFactory;

    /**
     * Um bloqueio sem horário definido cobre o dia inteiro.
     */
    public function isAllDay(): bool
    {
        return $this->start_time === null && $this->end_time === null;
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
