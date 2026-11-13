<?php



namespace App\Mail;



use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Address;

use Illuminate\Mail\Mailables\Attachment;

use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;



class TeamMessage extends Mailable

{

    /**

     * @param array<int, array{path: string, name: string, mime: string}> $files

     */

    public function __construct(

        public string $subjectLine,

        public string $body,

        public array $files = [],

    ) {}



    public function envelope(): Envelope

    {

        return new Envelope(

            from: new Address('no-reply@jnuaf.org', 'JNUAF'),

            replyTo: [new Address('team@jnuaf.org', 'JNUAF')],

            subject: $this->subjectLine,

        );

    }



    public function content(): Content

    {

        return new Content(

            view: 'mail.team-message',

            text: 'mail.team-message-text',

            with: [

                'body' => $this->body,

                'attachmentNames' => array_column($this->files, 'name'),

            ],

        );

    }



    public function attachments(): array

    {

        return array_map(

            fn (array $file) => Attachment::fromPath($file['path'])

                ->as($file['name'])

                ->withMime($file['mime']),

            $this->files

        );

    }

}

