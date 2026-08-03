<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TeamInviteMail extends Mailable
{
    public function __construct(public string $businessName, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Convite pra equipe de {$this->businessName}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.team-invite');
    }
}
