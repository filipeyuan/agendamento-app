<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessHourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $day_of_week
 * @property bool $is_open
 * @property string|null $start_time
 * @property string|null $end_time
 */
#[Fillable(['day_of_week', 'is_open', 'start_time', 'end_time'])]
class BusinessHour extends Model
{
    /** @use HasFactory<BusinessHourFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_open' => 'boolean',
        ];
    }
}
