<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class RecurringPaymentConfirmedMail extends Mailable
{
    public int $count;

    public string $serviceName;

    public string $firstWhen;

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    public function __construct(Collection $appointments)
    {
        /** @var Appointment $first */
        $first = $appointments->first();

        $this->count = $appointments->count();
        $this->serviceName = $first->service->name;
        $this->firstWhen = $first->start_at->format('d/m/Y').' às '.$first->start_at->format('H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pagamento confirmado: {$this->count}x {$this->serviceName}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.recurring-payment-confirmed');
    }
}
