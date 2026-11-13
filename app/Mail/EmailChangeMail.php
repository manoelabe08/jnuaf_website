<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EmailChangeMail extends Mailable
{
    public function __construct(
        public string $kind,
        public User $user,
        public string $oldEmail,
        public string $newEmail,
        public ?string $url = null,
        public ?string $ip = null,
        public bool $requiresApproval = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->kind) {
            'confirm' => 'Confirmez votre nouvelle adresse email JNUAF.ORG',
            'requested' => 'Demande de changement d’adresse email sur votre compte JNUAF.ORG',
            'applied_old', 'applied_new' => 'Votre adresse email JNUAF.ORG a été modifiée',
            'refused' => 'Changement d’adresse email refusé',
            'approval' => 'Validation requise : changement d’email d’un compte à accès complet',
            default => 'JNUAF.ORG',
        });
    }

    public function content(): Content
    {
        $tz = (string) config('jnuaf_newsletter.timezone', 'Europe/Paris');

        return new Content(
            view: 'mail.email-change',
            with: ['when' => now()->timezone($tz)->format('d/m/Y à H:i').' ('.$tz.')'],
        );
    }
}
