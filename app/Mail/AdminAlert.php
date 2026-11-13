<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Alerte envoyée au Bureau de Pilotage (nouvelle candidature, nouvelle demande partenaire…). */
class AdminAlert extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, string|null> $details */
    public function __construct(
        public string $title,
        public string $intro,
        public array $details,
        public string $url,
        public string $action = 'Ouvrir dans l\'administration',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'JNUAF admin : '.$this->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.admin-alert');
    }
}
