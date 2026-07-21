<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('+1 day', '+2 weeks');
        $endAt = (clone $startAt)->modify('+30 minutes');

        return [
            'user_id' => User::factory(),
            'service_id' => Service::factory(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => AppointmentStatus::Pending,
            'source' => AppointmentSource::Web,
            'notes' => null,
        ];
    }
}
