<?php

namespace App\Mail;



use App\Models\User;

use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;



class AccountAudit extends Mailable

{

    public function __construct(

        public string $action,

        public array $details,

        public ?User $actor,

        public ?string $ip,

    ) {}



    public function envelope(): Envelope

    {

        return new Envelope(subject: 'Audit comptes JNUAF : '.$this->action);

    }



    public function content(): Content

    {

        $tz = (string) config('jnuaf_newsletter.timezone', 'Europe/Paris');



        return new Content(

            view: 'mail.account-audit',

            with: [

                'when' => now()->timezone($tz)->format('d/m/Y à H:i').' ('.$tz.')',

            ],

        );

    }

}

