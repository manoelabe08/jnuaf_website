<?php

namespace App\Mail;



use App\Models\User;

use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;



class AccountCreated extends Mailable

{

    public function __construct(

        public User $user,

        public string $inviteUrl,
        public bool $isReset = false,
    ) {}



    public function envelope(): Envelope

    {

        return new Envelope(subject: $this->isReset ? 'Nouvelle invitation JNUAF.ORG' : 'Votre accès administrateur JNUAF.ORG');

    }



    public function content(): Content

    {

        $this->user->loadMissing('territory');



        return new Content(

            view: 'mail.account-invitation',

            with: [

                'loginUrl' => url('/admin/connexion'),

                'inviteUrl' => $this->inviteUrl, 'validHours' => 72,
                'isReset' => $this->isReset,
                'expiresAt' => null,

                'roleLabel' => $this->user->role?->label() ?? '',

                'territoryName' => optional($this->user->territory)->name,

            ],

        );

    }

}

