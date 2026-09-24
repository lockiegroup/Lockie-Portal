<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FindATenderService
{
    private const BASE_URL = 'https://www.find-tender.service.gov.uk/api/1.0/ocdsReleasePackages';

    // Keywords to match against tender title/description
    private const KEYWORDS = [
        'security seal',
        'tamper evident',
        'tamper-evident',
        'cable seal',
        'cable tie',
        'numbered seal',
        'pull tight seal',
        'pull-tight seal',
        'security tag',
        'container seal',
        'cage seal',
        'waste seal',
        'postal seal',
        'utility seal',
        'meter seal',
        'plastic seal',
        'bolt seal',
        'padlock',
        'hasp',
        'locking seal',
    ];

    public function fetchRecentOpportunities(int $daysBack = 7, $output = null): array
    {
        $results  = [];
        $fromDate = now()->subDays($daysBack)->format('Y-m-d') . 'T00:00:00Z';
        $cursor   = null;
        $page     = 0;
        $maxPages = 30;

        if ($output) $output->line("     [FAT] Fetching releases updated from {$fromDate}...");

        do {
            $params = ['updatedFrom' => $fromDate, 'limit' => 100];
            if ($cursor) $params['cursor'] = $cursor;

            try {
                $response = Http::timeout(30)->get(self::BASE_URL, $params);
            } catch (\Throwable $e) {
                Log::warning('FindATender request failed: ' . $e->getMessage());
                break;
            }

            if ($output) $output->line("     [FAT] Page " . ($page + 1) . " → HTTP " . $response->status());

            if (! $response->successful()) {
                Log::warning('FindATender API error ' . $response->status() . ': ' . $response->body());
                break;
            }

            $body     = $response->json();
            $cursor   = $body['cursor'] ?? null;

            if ($output && $page === 0) {
                $topKeys = is_array($body) ? implode(', ', array_keys($body)) : gettype($body);
                $output->line("     [FAT] Body top-level keys: {$topKeys}");
                $firstKey = is_array($body) ? array_key_first($body) : null;
                if ($firstKey && is_array($body[$firstKey]) && count($body[$firstKey]) > 0) {
                    $firstItem = $body[$firstKey][0];
                    $output->line("     [FAT] First [{$firstKey}][0] keys: " . implode(', ', array_keys($firstItem)));
                }
            }

            $releases = $this->extractReleases($body);

            if ($output) {
                $output->line("     [FAT] Got " . count($releases) . " releases on this page");
                if ($page === 1 && count($releases) > 0) {
                    $sample = $releases[0];
                    $output->line("     [FAT] Sample keys: " . implode(', ', array_keys($sample)));
                    $tender = $sample['tender'] ?? [];
                    $output->line("     [FAT] Sample tender keys: " . implode(', ', array_keys($tender)));
                    $output->line("     [FAT] Sample title: " . ($tender['title'] ?? $sample['id'] ?? '(none)'));
                }
            }

            foreach ($releases as $r) {
                if (! $this->matchesKeywords($r)) continue;
                $ref = $r['ocid'] ?? null;
                if ($ref && ! isset($results[$ref])) {
                    $results[$ref] = $this->normalise($r);
                }
            }

            $page++;

            if (count($releases) > 0 && $cursor) sleep(1);

        } while ($cursor && $page < $maxPages);

        if ($output) $output->line("     [FAT] Total matching: " . count($results));

        return array_values($results);
    }

    private function extractReleases(array $body): array
    {
        $releases = [];
        $packages = $body['releases'] ?? $body['releasePackages'] ?? [];

        foreach ($packages as $pkg) {
            if (isset($pkg['releases'])) {
                foreach ($pkg['releases'] as $r) {
                    $releases[] = $r;
                }
            } elseif (isset($pkg['ocid'])) {
                $releases[] = $pkg;
            }
        }

        return $releases;
    }

    private function matchesKeywords(array $r): bool
    {
        $tender = $r['tender'] ?? [];
        $text   = strtolower(
            ($tender['title'] ?? '') . ' ' .
            ($tender['description'] ?? '') . ' ' .
            ($r['description'] ?? '')
        );

        foreach (self::KEYWORDS as $kw) {
            if (str_contains($text, $kw)) return true;
        }

        return false;
    }

    private function normalise(array $r): array
    {
        $tender = $r['tender'] ?? [];
        $buyer  = $r['buyer'] ?? [];

        $valueHigh = null;
        if (isset($tender['value']['amount'])) {
            $valueHigh = (int) $tender['value']['amount'];
        }

        $cpvCodes = [];
        foreach ($tender['items'] ?? [] as $item) {
            foreach ($item['additionalClassifications'] ?? [] as $cls) {
                if (isset($cls['id'])) $cpvCodes[] = $cls['id'];
            }
        }

        $deadline      = $tender['tenderPeriod']['endDate'] ?? $tender['submissionDeadline'] ?? null;
        $contractStart = $tender['contractPeriod']['startDate'] ?? null;
        $contractEnd   = $tender['contractPeriod']['endDate'] ?? null;

        return [
            'source'         => 'find_a_tender',
            'source_ref'     => 'fat_' . ($r['ocid'] ?? uniqid()),
            'title'          => $tender['title'] ?? $r['id'] ?? 'Untitled',
            'description'    => $tender['description'] ?? null,
            'buyer_name'     => $buyer['name'] ?? null,
            'buyer_location' => $buyer['address']['locality'] ?? null,
            'value_low'      => null,
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
