<?php



namespace App\Mail;



use App\Models\User;

use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;



class TwoFactorCode extends Mailable

{

    public function __construct(

        public User $user,

        public string $code,

        public int $minutes,

        public ?string $ip = null,

    ) {}



    public function envelope(): Envelope

    {

        return new Envelope(subject: 'Votre code de connexion JNUAF.ORG');

    }



    public function content(): Content

    {

        $tz = (string) config('jnuaf_newsletter.timezone', 'Europe/Paris');



        return new Content(

            view: 'mail.two-factor-code',

            with: ['when' => now()->timezone($tz)->format('d/m/Y à H:i').' ('.$tz.')'],

        );

    }

}

