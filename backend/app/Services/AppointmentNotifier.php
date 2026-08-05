<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentRescheduledMail;
use App\Models\Appointment;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentRescheduledNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AppointmentNotifier
{
    public function notifyCancelled(Appointment $appointment): void
    {
        $appointment->loadMissing(['service', 'user', 'business', 'staff']);

        $this->sendMailSafely($appointment, new AppointmentCancelledMail($appointment));
        $this->notifySafely($appointment, new AppointmentCancelledNotification($appointment));
    }

    public function notifyRescheduled(Appointment $appointment): void
    {
        $appointment->loadMissing(['service', 'user', 'business', 'staff']);

        $this->sendMailSafely($appointment, new AppointmentRescheduledMail($appointment));
        $this->notifySafely($appointment, new AppointmentRescheduledNotification($appointment));
    }

    private function sendMailSafely(Appointment $appointment, Mailable $mailable): void
    {
        try {
            Mail::to($appointment->user->email)->send($mailable);
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar e-mail de status de agendamento.', [
                'appointment_id' => $appointment->id,
                'mailable' => $mailable::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function notifySafely(Appointment $appointment, Notification $notification): void
    {
        try {
            $appointment->user->notify($notification);
        } catch (Throwable $e) {
            Log::warning('Falha ao criar notificação de status de agendamento.', [
                'appointment_id' => $appointment->id,
                'notification' => $notification::class,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
