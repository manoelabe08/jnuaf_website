<?php



namespace App\Mail;



use Carbon\CarbonInterface;

use Illuminate\Bus\Queueable;

use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;

use Illuminate\Queue\SerializesModels;



class NewDeviceLogin extends Mailable

{

    use Queueable, SerializesModels;



    public function __construct(

        public string $name,

        public string $device,

        public string $ip,

        public CarbonInterface $at,

    ) {}



    public function envelope(): Envelope

    {

        return new Envelope(subject: 'JNUAF : nouvelle connexion à votre compte');

    }



    public function content(): Content

    {

        return new Content(view: 'mail.new-device-login');

    }

}

