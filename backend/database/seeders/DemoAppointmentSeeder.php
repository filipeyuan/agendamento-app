<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Popula um histórico de agendamentos de demonstração (passado e futuro) pra que o
 * dashboard de analytics e o calendário do admin não fiquem vazios num banco novo.
 * Só roda se ainda não existir nenhum agendamento, então é seguro no boot do container.
 */
class DemoAppointmentSeeder extends Seeder
{
    public function run(): void
    {
        if (Appointment::query()->exists()) {
            return;
        }

        $services = Service::all();
        if ($services->isEmpty()) {
            return;
        }

        $client = User::firstOrCreate(
            ['email' => 'cliente.demo@zelo.test'],
            [
                'name' => 'Cliente Demo',
                'password' => Hash::make('demo12345'),
                'role' => UserRole::Client,
            ]
        );

        // [deslocamento em dias a partir de hoje, hora, status, índice do serviço]
        $entries = [
            [-24, 9, AppointmentStatus::Completed, 0],
            [-22, 14, AppointmentStatus::Completed, 2],
            [-19, 10, AppointmentStatus::Completed, 1],
            [-17, 16, AppointmentStatus::Cancelled, 3],
            [-15, 9, AppointmentStatus::Completed, 0],
            [-13, 11, AppointmentStatus::Completed, 2],
            [-11, 15, AppointmentStatus::Completed, 1],
            [-9, 9, AppointmentStatus::Completed, 0],
            [-7, 13, AppointmentStatus::Completed, 2],
            [-6, 10, AppointmentStatus::Completed, 0],
            [-4, 16, AppointmentStatus::Completed, 3],
            [-2, 9, AppointmentStatus::Completed, 1],
            [-1, 14, AppointmentStatus::Completed, 2],
            [1, 9, AppointmentStatus::Confirmed, 0],
            [2, 11, AppointmentStatus::Pending, 1],
            [4, 15, AppointmentStatus::Confirmed, 2],
            [6, 10, AppointmentStatus::Confirmed, 0],
            [9, 9, AppointmentStatus::Pending, 3],
        ];

        foreach ($entries as [$dayOffset, $hour, $status, $serviceIndex]) {
            $service = $services[$serviceIndex % $services->count()];
            $startAt = now()->addDays($dayOffset)->setTime($hour, 0);

            Appointment::create([
                'user_id' => $client->id,
                'service_id' => $service->id,
                'start_at' => $startAt,
                'end_at' => $startAt->clone()->addMinutes($service->duration_minutes),
                'status' => $status,
                'payment_status' => $status === AppointmentStatus::Cancelled ? PaymentStatus::Pending : PaymentStatus::Paid,
                'source' => AppointmentSource::Web,
                'notes' => null,
            ]);
        }
    }
}
