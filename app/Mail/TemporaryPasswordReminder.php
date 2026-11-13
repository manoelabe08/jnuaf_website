<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TemporaryPasswordReminder extends Mailable
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre mot de passe temporaire JNUAF.ORG expire bientôt');
    }

    public function content(): Content
    {
        $tz = (string) config('jnuaf_newsletter.timezone', 'Europe/Paris');

        return new Content(
            view: 'mail.password-expiring',
            with: [
                'loginUrl' => url('/admin/connexion'),
                'forgotUrl' => url('/admin/mot-de-passe-oublie'),
                'expiresAt' => optional($this->user->password_expires_at)->timezone($tz)->format('d/m/Y à H:i'),
            ],
        );
    }
}
