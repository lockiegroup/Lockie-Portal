<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class HsActionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Collection $dueSoon,
        public readonly Collection $overdue,
        public readonly int $daysBefore,
    ) {}

    public function envelope(): Envelope
    {
        $overdueCount = $this->overdue->count();
        $dueSoonCount = $this->dueSoon->count();

        $subject = match (true) {
            $overdueCount > 0 && $dueSoonCount > 0 => "H&S Actions — {$overdueCount} overdue, {$dueSoonCount} due soon",
            $overdueCount > 0                       => "H&S Actions — {$overdueCount} overdue",
            default                                 => "H&S Actions — {$dueSoonCount} due within {$this->daysBefore} days",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.hs-action-reminder');
    }
}
