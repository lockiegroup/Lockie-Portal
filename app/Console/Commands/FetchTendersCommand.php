<?php

namespace App\Console\Commands;

use App\Models\Tender;
use App\Services\ContractsFinderService;
use App\Services\FindATenderService;
use App\Services\TenderAIService;
use Illuminate\Console\Command;

class FetchTendersCommand extends Command
{
    protected $signature = 'tender:fetch
                            {--days=7 : How many days back to search}
                            {--source=all : Which source to fetch (all, contracts_finder, find_a_tender)}
                            {--rescore : Re-score existing tenders with AI}
                            {--debug : Dump raw API responses for debugging}';

    protected $description = 'Fetch new tender opportunities from UK procurement sources and score with AI';

    public function handle(
        ContractsFinderService $contractsFinder,
        FindATenderService     $findATender,
        TenderAIService        $ai,
    ): int {
        $days   = (int) $this->option('days');
        $source = $this->option('source');

        $this->info("Fetching tenders from the past {$days} days...");

        $raw = [];

        $debug = (bool) $this->option('debug');

        if (in_array($source, ['all', 'contracts_finder'])) {
            $this->line('  → Contracts Finder...');
            $cfResults = $contractsFinder->fetchRecentOpportunities($days, $debug ? $this : null);
            $raw = array_merge($raw, $cfResults);
            $this->line('     Found ' . count($cfResults) . ' from Contracts Finder');
        }

        if (in_array($source, ['all', 'find_a_tender'])) {
            $this->line('  → Find a Tender...');
            $fatResults = $findATender->fetchRecentOpportunities($days, $debug ? $this : null);
            $raw        = array_merge($raw, $fatResults);
            $this->line('     Found ' . count($fatResults) . ' from Find a Tender');
        }

        $new = 0;
        $scored = 0;

        foreach ($raw as $data) {
            $ref = $data['source_ref'] ?? null;
            if (! $ref) continue;

            $existing = Tender::where('source_ref', $ref)->first();

            if ($existing) {
                // Update deadline/value if changed
                $existing->fill(array_filter([
                    'deadline_at' => $data['deadline_at'],
                    'value_high'  => $data['value_high'],
                ]));
                if ($existing->isDirty()) $existing->save();
                continue;
            }

            // Score with AI
            $aiData = $ai->score($data);
            $scored++;

            Tender::create(array_merge($data, $aiData, ['status' => 'new']));
            $new++;

            if ($new % 10 === 0) {
                $this->line("  ... {$new} new tenders saved");
            }
        }

        $this->info("Done. {$new} new tenders saved, {$scored} scored with AI.");

        if ($this->option('rescore')) {
            $this->rescoreExisting($ai);
        }

        return self::SUCCESS;
    }

    private function rescoreExisting(TenderAIService $ai): void
    {
        $tenders = Tender::whereNull('ai_score')->orWhere('ai_score', 0)->get();
        $this->line("Re-scoring {$tenders->count()} unscored tenders...");

        foreach ($tenders as $tender) {
            $result = $ai->score($tender->toArray());
            $tender->update($result);
            sleep(1);
        }

        $this->info('Re-scoring complete.');
    }
}
