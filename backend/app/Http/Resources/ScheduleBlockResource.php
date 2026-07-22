<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ScheduleBlock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScheduleBlock */
class ScheduleBlockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'start_time' => $this->start_time === null ? null : substr($this->start_time, 0, 5),
            'end_time' => $this->end_time === null ? null : substr($this->end_time, 0, 5),
            'reason' => $this->reason,
        ];
    }
}
