<?php

namespace App\Mail;

use App\Models\Registration;
use App\Services\BadgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttestationAvailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Merci pour votre participation ('.$this->registration->eventTitle().')'.' : votre attestation');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.attestation');
    }

    public function attachments(): array
    {
        $pdf = app(BadgeService::class);

        return [
            Attachment::fromData(fn () => $pdf->attestationPdf($this->registration), $pdf->attestationFilename($this->registration))
                ->withMime('application/pdf'),
        ];
    }
}
