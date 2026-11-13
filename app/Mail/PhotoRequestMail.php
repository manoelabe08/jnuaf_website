<?php



namespace App\Mail;



use Illuminate\Bus\Queueable;

use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;

use Illuminate\Queue\SerializesModels;



class PhotoRequestMail extends Mailable

{

    use Queueable, SerializesModels;



    public function __construct(public string $first, public string $url, public int $days)

    {

    }



    public function envelope(): Envelope

    {

        return new Envelope(from: new Address('no-reply@jnuaf.org', 'JNUAF'), subject: 'Action requise : envoyez votre photo pour votre carte de membre JNUAF');

    }



    public function content(): Content

    {

        return new Content(view: 'mail.photo-request', text: 'mail.photo-request-text');

    }

}

