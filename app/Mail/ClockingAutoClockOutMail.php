<?php

namespace App\Mail;

use App\Models\ClockingCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClockingAutoClockOutMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ?ClockingCategory $category,
        /** @var array<int, array{name: string, clocked_in_at: string, date: string}> */
        public readonly array $employees,
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->employees);
        $label = $count === 1 ? $this->employees[0]['name'] : "{$count} employees";

        return new Envelope(
            subject: "Clocking: {$label} auto clocked out at midnight — please review",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.clocking-auto-clockout');
    }
}
