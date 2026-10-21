<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ImportFailedMail extends Mailable
{
    public function __construct(
        public string $importType,
        public string $errorMessage,
        public string $occurredAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Import Failed: {$this->importType}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.import-failed');
    }
}
