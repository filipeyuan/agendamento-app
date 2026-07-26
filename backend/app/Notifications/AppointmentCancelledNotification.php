<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $when = $this->appointment->start_at->format('d/m/Y').' às '.$this->appointment->start_at->format('H:i');

        return [
            'type' => 'appointment_cancelled',
            'appointment_id' => $this->appointment->id,
            'message' => "Agendamento cancelado: {$this->appointment->service->name} em {$when}",
        ];
    }
}
