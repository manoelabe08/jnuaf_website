<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationWaitlisted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Registration $registration, public string $manageToken) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->registration->eventTitle().' : vous êtes sur liste d\'attente');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.registration-waitlisted', with: [
            'manageUrl' => route('registration.manage', $this->manageToken),
        ]);
    }
}
