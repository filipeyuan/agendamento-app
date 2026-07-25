<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AppointmentCancelledMail extends Mailable
{
    public string $when;

    public function __construct(public Appointment $appointment)
    {
        $this->when = $appointment->start_at->format('d/m/Y').' às '.$appointment->start_at->format('H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Agendamento cancelado: '.$this->appointment->service->name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.appointment-cancelled');
    }
}
