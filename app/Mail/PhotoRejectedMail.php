<?php



namespace App\Mail;



use Illuminate\Bus\Queueable;

use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Address;

use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;

use Illuminate\Queue\SerializesModels;



class PhotoRejectedMail extends Mailable

{

    use Queueable, SerializesModels;



    public function __construct(public string $first, public string $url, public int $days, public string $reason)

    {

    }



    public function envelope(): Envelope

    {

        return new Envelope(

            from: new Address('no-reply@jnuaf.org', 'JNUAF'),

            subject: "Votre photo doit \u{ea}tre remplac\u{e9}e : photo d'identit\u{e9} pr\u{e9}sentable requise"

        );

    }



    public function content(): Content

    {

        return new Content(view: 'mail.photo-rejected', text: 'mail.photo-rejected-text');

    }

}

