<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ScheduleBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleBlock>
 */
class ScheduleBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->addDay()->toDateString(),
            'start_time' => null,
            'end_time' => null,
            'reason' => null,
        ];
    }
}
