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

class EventReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Rappel : '.$this->registration->eventTitle().' approche');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.event-reminder');
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
