<?php

namespace App\Console\Commands;

use App\Mail\TenderRadarDailySummaryMail;
use App\Models\Tender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TenderRadarAlertCommand extends Command
{
    protected $signature = 'tender:alert
                            {--email= : Override recipient email}
                            {--force : Send even if no new tenders}';

    protected $description = 'Send daily Tender Radar summary email';

    public function handle(): int
    {
        $email = $this->option('email') ?: config('services.tender_radar.alert_email');

        if (! $email) {
            $this->warn('No alert email configured. Set TENDER_RADAR_ALERT_EMAIL in .env');
            return self::FAILURE;
        }

        // High and relevant tenders not yet alerted
        $newHigh = Tender::where('status', 'new')
            ->whereIn('ai_relevance', ['high', 'relevant'])
            ->whereNull('alerted_at')
            ->orderByDesc('ai_score')
            ->get();

        // Possible tenders not yet alerted
        $newPossible = Tender::where('status', 'new')
            ->where('ai_relevance', 'possible')
            ->whereNull('alerted_at')
            ->orderByDesc('ai_score')
            ->limit(10)
            ->get();

        // Closing soon (within 14 days, not yet alerted today)
        $closingSoon = Tender::whereNotIn('status', ['ignored', 'lost'])
            ->whereIn('ai_relevance', ['high', 'relevant'])
            ->whereBetween('deadline_at', [now(), now()->addDays(14)])
            ->where(function ($q) {
                $q->whereNull('alerted_at')
                  ->orWhere('alerted_at', '<', now()->subDays(3));
            })
            ->orderBy('deadline_at')
            ->get();

        $totalMeaningful = $newHigh->count() + $closingSoon->count();

        if ($totalMeaningful === 0 && ! $this->option('force')) {
            $this->info('No meaningful new tenders today. Skipping alert.');
            return self::SUCCESS;
        }

        Mail::to($email)->send(new TenderRadarDailySummaryMail(
            newHigh:     $newHigh,
            newPossible: $newPossible,
            closingSoon: $closingSoon,
        ));

        // Mark alerted
        $alerted = $newHigh->merge($newPossible)->merge($closingSoon);
        Tender::whereIn('id', $alerted->pluck('id'))->update(['alerted_at' => now()]);

        $this->info("Alert sent to {$email}. {$alerted->count()} tenders included.");
        return self::SUCCESS;
    }
}
