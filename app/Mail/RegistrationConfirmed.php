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

class RegistrationConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    /** @param bool $promoted true quand une place libérée est attribuée depuis la liste d'attente */
    public function __construct(public Registration $registration, public string $manageToken, public bool $promoted = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->promoted
            ? $this->registration->eventTitle().' : une place s\'est libérée, voici votre badge'
            : $this->registration->eventTitle().' : votre inscription et votre badge');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.registration-confirmed', with: [
            'manageUrl' => route('registration.manage', $this->manageToken),
        ]);
    }

    public function attachments(): array
    {
        $badges = app(BadgeService::class);

        return [
            Attachment::fromData(fn () => $badges->pdf($this->registration), $badges->filename($this->registration))
                ->withMime('application/pdf'),
        ];
    }
}
