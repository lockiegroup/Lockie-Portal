<?php

namespace App\Mail;

use App\Models\PrintJob;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrintJobDateChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PrintJob $job,
        public readonly ?string  $oldDate,
        public readonly string   $newDate,
        public readonly string   $changedBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Delivery Date Changed — {$this->job->order_number} ({$this->job->product_code})",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.print-job-date-changed');
    }
}
