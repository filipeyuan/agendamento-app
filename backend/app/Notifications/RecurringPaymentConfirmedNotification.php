<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class RecurringPaymentConfirmedNotification extends Notification
{
    use Queueable;

    public int $count;

    public string $serviceName;

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    public function __construct(Collection $appointments)
    {
        /** @var Appointment $first */
        $first = $appointments->first();

        $this->count = $appointments->count();
        $this->serviceName = $first->service->name;
    }

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
        return [
            'type' => 'payment_confirmed',
            'appointment_id' => null,
            'message' => "Pagamento confirmado: {$this->count}x {$this->serviceName} (agendamento recorrente)",
        ];
    }
}
