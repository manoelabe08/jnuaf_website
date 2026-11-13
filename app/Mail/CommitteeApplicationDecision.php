<?php

namespace App\Mail;

use App\Enums\ApplicationStatus;
use App\Models\CommitteeApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CommitteeApplicationDecision extends Mailable
{
    public function __construct(
        public CommitteeApplication $application,
        public ApplicationStatus $decision,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->decision === ApplicationStatus::Accepted
                ? 'JNUAF : votre candidature au comité est retenue'
                : 'JNUAF : réponse à votre candidature au comité'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.committee-decision'
        );
    }
}