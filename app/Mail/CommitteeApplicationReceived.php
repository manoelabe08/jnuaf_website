<?php

namespace App\Mail;

use App\Models\CommitteeApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommitteeApplicationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CommitteeApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'JNUAF : nous avons bien reçu votre candidature');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.committee-received');
    }
}
