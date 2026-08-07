<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentNotifier;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Envia o lembrete por e-mail dos agendamentos confirmados que estão prestes a acontecer';

    public function handle(AppointmentNotifier $notifier): int
    {
        $hoursBefore = (int) config('booking.reminder_hours_before');

        $appointments = Appointment::query()
            ->where('status', AppointmentStatus::Confirmed)
            ->whereNull('reminder_sent_at')
            ->where('start_at', '>', now())
            ->where('start_at', '<=', now()->addHours($hoursBefore))
            ->get();

        foreach ($appointments as $appointment) {
            $notifier->notifyReminder($appointment);
            $appointment->update(['reminder_sent_at' => now()]);
        }

        $this->info("Lembretes enviados: {$appointments->count()}.");

        return self::SUCCESS;
    }
}
