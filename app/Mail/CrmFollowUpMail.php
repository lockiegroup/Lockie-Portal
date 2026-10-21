<?php

namespace App\Mail;

use App\Models\KeyAccountContact;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CrmFollowUpMail extends Mailable
{
    public function __construct(public KeyAccountContact $contact) {}

    public function envelope(): Envelope
    {
        $code = $this->contact->account?->account_code ?? 'Unknown';
        return new Envelope(subject: "CRM Follow-up Due: {$code}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.crm-follow-up');
    }
}
