<?php



namespace App\Mail;



use App\Models\MemberCard;

use App\Services\MemberCardService;

use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Address;

use Illuminate\Mail\Mailables\Attachment;

use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;



class MemberCardMessage extends Mailable

{

    public function __construct(

        public MemberCard $card,

        public string $pdf,

        public string $filename,

    ) {}



    public function envelope(): Envelope

    {

        return new Envelope(

            from: new Address('no-reply@jnuaf.org', 'JNUAF'),

            replyTo: [new Address('team@jnuaf.org', 'JNUAF')],

            subject: 'Votre carte de membre JNUAF',

        );

    }



    public function content(): Content

    {

        return new Content(

            view: 'mail.member-card',

            text: 'mail.member-card-text',

            with: [

                'infoUrl' => url('/carte-info'),

                'categoryLabel' => MemberCardService::CATEGORIES[$this->card->category] ?? $this->card->category,

            ],

        );

    }



    public function attachments(): array

    {

        return [

            Attachment::fromData(fn () => $this->pdf, $this->filename)->withMime('application/pdf'),

        ];

    }

}

