<?php

namespace App\Mail;

use App\Models\PartnerInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerInquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PartnerInquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '#JNUAF.ORG : merci pour votre proposition de partenariat');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.partner-received');
    }
}
