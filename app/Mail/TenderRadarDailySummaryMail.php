<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenderRadarDailySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Collection $newHigh,
        public readonly Collection $newPossible,
        public readonly Collection $closingSoon,
    ) {}

    public function envelope(): Envelope
    {
        $date     = now()->format('j F Y');
        $highCount = $this->newHigh->where('ai_relevance', 'high')->count();
        $subject  = $highCount > 0
            ? "🔥 Lockie Tender Radar – {$date} ({$highCount} high-priority)"
            : "Lockie Tender Radar – {$date}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tender-radar-daily');
    }
}
