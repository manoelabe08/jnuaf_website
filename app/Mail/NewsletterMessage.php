<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Services\NewsletterService;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

class NewsletterMessage extends Mailable
{
    public function __construct(
        public NewsletterCampaign $campaign,
        public ?NewsletterDelivery $delivery = null
    ) {}

    private function urls(): array
    {
        if (! $this->delivery) {
            return [null, null];
        }

        $token = app(NewsletterService::class)
            ->token($this->delivery);

        return [
            route('newsletter.web', $token),
            route('newsletter.unsubscribe', $token),
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                'no-reply@jnuaf.org',
                'Équipe JNUAF'
            ),

            replyTo: [
                new Address(
                    'journeedunumeriqueaufeminin@gmail.com',
                    'Équipe JNUAF'
                ),
            ],

            subject: ($this->delivery ? '' : '[TEST] ')
                .$this->campaign->subject
        );
    }

    public function content(): Content
    {
        [$webUrl, $unsubscribeUrl] = $this->urls();

        $recipientName = $this->delivery
            ? trim((string) $this->delivery->subscriber?->name)
            : '';

        return new Content(
            view: 'mail.newsletter',

            text: 'mail.newsletter-text',

            with: [
                'campaign' => $this->campaign,
                'webUrl' => $webUrl,
                'unsubscribeUrl' => $unsubscribeUrl,
                'recipientName' => $recipientName,
            ]
        );
    }

    public function headers(): Headers
    {
        [, $unsubscribeUrl] = $this->urls();

        return new Headers(
            text: $unsubscribeUrl
                ? [
                    'List-Unsubscribe' => '<'.$unsubscribeUrl.'>',
                ]
                : []
        );
    }

    public function attachments(): array
    {
        return $this->campaign->attachments
            ->map(function ($file) {
                return Attachment::fromPath(
                    app(NewsletterService::class)
                        ->attachmentPath($file)
                )
                    ->as($file->download_name)
                    ->withMime($file->mime);
            })
            ->all();
    }
}