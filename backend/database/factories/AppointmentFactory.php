<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
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
            // Deriva do serviço em vez de criar um Business::factory() próprio, assim um
            // agendamento sempre pertence ao mesmo negócio do serviço escolhido, mesmo
            // quando o teste só passa 'service_id' explicitamente.
            'business_id' => fn (array $attributes) => Service::find($attributes['service_id'])?->business_id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => AppointmentStatus::Pending,
            'payment_status' => PaymentStatus::Paid,
            'source' => AppointmentSource::Web,
            'notes' => null,
        ];
    }
}
