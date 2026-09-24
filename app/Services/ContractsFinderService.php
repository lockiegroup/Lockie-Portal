<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContractsFinderService
{
    private const BASE_URL = 'https://www.contractsfinder.service.gov.uk/Published/Notices/PublicAPI';

    private const SEARCH_TERMS = [
        'security seals',
        'tamper evident seals',
        'tamper-evident seals',
        'cable seals',
        'cable ties',
        'numbered seals',
        'pull tight seals',
        'pull-tight seals',
        'security tags',
        'container seals',
        'cage seals',
        'waste seals',
        'postal seals',
        'utility seals',
        'meter seals',
        'plastic seals',
        'bolt seals',
    ];

    public function fetchRecentOpportunities(int $daysBack = 7): array
    {
        $results   = [];
        $seen      = [];
        $fromDate  = now()->subDays($daysBack)->format('Y-m-d');

        foreach (self::SEARCH_TERMS as $term) {
            try {
                $notices = $this->searchNotices($term, $fromDate);
                foreach ($notices as $notice) {
                    $ref = $notice['Id'] ?? null;
                    if (! $ref || isset($seen[$ref])) continue;
                    $seen[$ref]  = true;
                    $results[]   = $this->normalise($notice);
                }
                sleep(1); // be polite to the API
            } catch (\Throwable $e) {
                Log::warning("ContractsFinder search failed for term '{$term}': " . $e->getMessage());
            }
        }

        return $results;
    }

    private function searchNotices(string $term, string $fromDate): array
    {
        $response = Http::timeout(30)->post(self::BASE_URL . '/SearchPublicNoticesParameters', [
            'searchTerm'      => $term,
            'publishedFrom'   => $fromDate,
            'publishedTo'     => now()->format('Y-m-d'),
            'noticeTypes'     => ['Contract Notice', 'Prior Information Notice'],
            'sortBy'          => 'PublishedDate',
            'descending'      => true,
            'size'            => 50,
            'from'            => 0,
        ]);

        if (! $response->successful()) {
            Log::warning('ContractsFinder API error: ' . $response->status());
            return [];
        }

        return $response->json('notices') ?? [];
    }

    private function normalise(array $notice): array
    {
        $value = $notice['EstimatedValue'] ?? null;

        return [
            'source'       => 'contracts_finder',
            'source_ref'   => 'cf_' . ($notice['Id'] ?? uniqid()),
            'title'        => $notice['Title'] ?? 'Untitled',
            'description'  => $notice['Description'] ?? null,
            'buyer_name'   => $notice['OrganisationName'] ?? null,
            'buyer_location' => $notice['PostcodeArea'] ?? null,
            'value_low'    => null,
            'value_high'   => $value ? (int) $value : null,
            'published_at' => isset($notice['PublishedDate'])
                ? substr($notice['PublishedDate'], 0, 10)
                : null,
            'deadline_at'  => $notice['CloseDate'] ?? $notice['DeadlineDate'] ?? null,
            'contract_start' => null,
            'contract_end'   => null,
            'source_url'   => $notice['NoticeUrl'] ?? null,
            'cpv_codes'    => $notice['CpvCodes'] ?? [],
        ];
    }
}
