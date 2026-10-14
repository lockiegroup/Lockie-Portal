<?php

namespace App\Mail;

use App\Models\ClockingEmployee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClockingClockoutAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ClockingEmployee $employee,
        public readonly string $scheduledEnd,
        public readonly string $currentTime,
        public readonly string $date,
        public readonly string $clockedInAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Clocking alert: {$this->employee->name} hasn't clocked out",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.clocking-clockout-alert');
    }
}
