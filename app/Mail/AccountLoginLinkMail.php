<?php



namespace App\Mail;



use App\Models\User;

use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;



class AccountLoginLinkMail extends Mailable

{

    public function __construct(

        public User $user,

        public string $url,

        public int $minutes,

    ) {}



    public function envelope(): Envelope

    {

        return new Envelope(subject: 'JNUAF : votre lien de connexion');

    }



    public function content(): Content

    {

        return new Content(view: 'mail.account-login-link');

    }

}

