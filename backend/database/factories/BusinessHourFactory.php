<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BusinessHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessHour>
 */
class BusinessHourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day_of_week' => fake()->numberBetween(0, 6),
            'is_open' => true,
            'start_time' => '09:00',
            'end_time' => '18:00',
        ];
    }
}
