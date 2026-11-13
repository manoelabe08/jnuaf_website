<?php



namespace App\Mail;



use App\Models\CommitteeApplication;

use App\Models\CommitteePoleProposal;

use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;



class CommitteePoleProposalMail extends Mailable

{

    public function __construct(

        public CommitteeApplication $application,

        public CommitteePoleProposal $proposal,

        public string $url,

    ) {}



    public function envelope(): Envelope

    {

        return new Envelope(subject: "JNUAF : proposition de p\u{f4}le pour votre candidature au comit\u{e9}");

    }



    public function content(): Content

    {

        return new Content(view: 'mail.committee-pole-proposal');

    }

}

