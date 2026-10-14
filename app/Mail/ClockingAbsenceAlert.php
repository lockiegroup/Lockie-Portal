<?php

namespace App\Mail;

use App\Models\ClockingEmployee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClockingAbsenceAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClockingEmployee $employee,
        public string $scheduledStart,
        public string $currentTime,
        public string $date,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Clocking alert: {$this->employee->name} hasn't clocked in",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.clocking-absence-alert');
    }
}
