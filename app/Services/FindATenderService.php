<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FindATenderService
{
    private const BASE_URL = 'https://www.find-tender.service.gov.uk/api/1.0/ocdsReleasePackages';

    private const SEARCH_TERMS = [
        'security seals',
        'tamper evident seals',
        'cable seals',
        'cable ties security',
        'numbered seals',
        'pull tight seals',
        'security tags identification',
        'container seals',
        'waste seals',
        'postal seals',
        'utility meter seals',
    ];

    public function fetchRecentOpportunities(int $daysBack = 7, $output = null): array
    {
        $results  = [];
        $seen     = [];
        $fromDate = now()->subDays($daysBack)->format('Y-m-d') . 'T00:00:00Z';

        foreach (self::SEARCH_TERMS as $term) {
            try {
                $releases = $this->searchReleases($term, $fromDate, $output);
                foreach ($releases as $release) {
                    $ref = $release['ocid'] ?? null;
                    if (! $ref || isset($seen[$ref])) continue;
                    $seen[$ref] = true;
                    $results[]  = $this->normalise($release);
                }
                sleep(1);
            } catch (\Throwable $e) {
                Log::warning("FindATender search failed for term '{$term}': " . $e->getMessage());
                if ($output) $output->line("     ERROR for '{$term}': " . $e->getMessage());
            }
        }

        return $results;
    }

    private function searchReleases(string $term, string $fromDate, $output = null): array
    {
        $response = Http::timeout(30)->get(self::BASE_URL, [
            'publishedFrom' => $fromDate,
            'q'             => $term,
        ]);

        if ($output) {
            $output->line("     [FAT] '{$term}' → HTTP " . $response->status());
            $output->line("     [FAT] Body (first 300): " . substr($response->body(), 0, 300));
        }

        if (! $response->successful()) {
            Log::warning('FindATender API error ' . $response->status() . ': ' . $response->body());
            return [];
        }

        $body = $response->json();

        if ($output) {
            $keys = is_array($body) ? implode(', ', array_keys($body)) : gettype($body);
            $output->line("     [FAT] JSON keys: {$keys}");
        }

        // OCDS package: top-level "releases" is an array of release packages,
        // each having a "releases" sub-array. Or the top-level may directly be releases.
        $rawPackages = $body['releases'] ?? $body['releasePackages'] ?? [];

        if ($output) $output->line("     [FAT] packages: " . count($rawPackages));

        $releases = [];
        foreach ($rawPackages as $pkg) {
            if (isset($pkg['releases'])) {
                foreach ($pkg['releases'] as $r) {
                    $releases[] = $r;
                }
            } elseif (isset($pkg['ocid'])) {
                $releases[] = $pkg;
            }
        }

        if ($output) $output->line("     [FAT] '{$term}': " . count($releases) . " releases");

        return $releases;
    }

    private function normalise(array $r): array
    {
        $tender  = $r['tender'] ?? [];
        $buyer   = $r['buyer'] ?? [];
        $awards  = $r['awards'] ?? [];
        $lots    = $tender['lots'] ?? [];

        $valueLow  = null;
        $valueHigh = null;
        if (isset($tender['value']['amount'])) {
            $valueHigh = (int) $tender['value']['amount'];
        }

        $cpvCodes = [];
        foreach ($tender['items'] ?? [] as $item) {
            foreach ($item['classification'] ?? [] as $cls) {
                if (isset($cls['id'])) $cpvCodes[] = $cls['id'];
            }
            foreach ($item['additionalClassifications'] ?? [] as $cls) {
                if (isset($cls['id'])) $cpvCodes[] = $cls['id'];
            }
        }

        $deadline = $tender['tenderPeriod']['endDate']
            ?? $tender['submissionDeadline']
            ?? null;

        $contractStart = $tender['contractPeriod']['startDate'] ?? null;
        $contractEnd   = $tender['contractPeriod']['endDate'] ?? null;

        return [
            'source'         => 'find_a_tender',
            'source_ref'     => 'fat_' . ($r['ocid'] ?? uniqid()),
            'title'          => $tender['title'] ?? $r['id'] ?? 'Untitled',
            'description'    => $tender['description'] ?? null,
            'buyer_name'     => $buyer['name'] ?? null,
            'buyer_location' => $buyer['address']['locality'] ?? null,
            'value_low'      => $valueLow,
            'value_high'     => $valueHigh,
            'published_at'   => isset($r['date']) ? substr($r['date'], 0, 10) : null,
            'deadline_at'    => $deadline,
            'contract_start' => $contractStart ? substr($contractStart, 0, 10) : null,
            'contract_end'   => $contractEnd   ? substr($contractEnd, 0, 10)   : null,
            'source_url'     => 'https://www.find-tender.service.gov.uk/Notice/' . urlencode($r['ocid'] ?? ''),
            'cpv_codes'      => array_values(array_unique($cpvCodes)),
        ];
    }
}
