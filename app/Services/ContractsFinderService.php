<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Contracts Finder below-threshold procurement notices.
 *
 * The v1 PublicAPI was decommissioned. V2 requires OAuth credentials
 * (client_credentials). If CONTRACTS_FINDER_CLIENT_ID / _CLIENT_SECRET
 * are set in .env this service will authenticate and search; otherwise
 * it returns an empty result set gracefully.
 */
class ContractsFinderService
{
    private const TOKEN_URL  = 'https://www.contractsfinder.service.gov.uk/oauth/token';
    private const SEARCH_URL = 'https://www.contractsfinder.service.gov.uk/Published/Notices/PublicAPI/V2/SearchPublicNoticesParameters';

    // Keywords to match against notice title/description
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
        'seals',
    ];

    public function fetchRecentOpportunities(int $daysBack = 7, $output = null): array
    {
        $clientId     = config('services.contracts_finder.client_id');
        $clientSecret = config('services.contracts_finder.client_secret');

        if (! $clientId || ! $clientSecret) {
            if ($output) $output->line("     [CF] No credentials set — skipping (set CONTRACTS_FINDER_CLIENT_ID/SECRET in .env)");
            return [];
        }

        $token = $this->getAccessToken($clientId, $clientSecret, $output);
        if (! $token) return [];

        $results  = [];
        $seen     = [];
        $fromDate = now()->subDays($daysBack)->format('Y-m-d');

        foreach (self::KEYWORDS as $term) {
            try {
                $notices = $this->searchNotices($token, $term, $fromDate, $output);
                foreach ($notices as $notice) {
                    $ref = $notice['Id'] ?? null;
                    if (! $ref || isset($seen[$ref])) continue;
                    $seen[$ref] = true;
                    $results[]  = $this->normalise($notice);
                }
                sleep(1);
            } catch (\Throwable $e) {
                Log::warning("ContractsFinder search failed for term '{$term}': " . $e->getMessage());
                if ($output) $output->line("     [CF] ERROR for '{$term}': " . $e->getMessage());
            }
        }

        return $results;
    }

    private function getAccessToken(string $clientId, string $clientSecret, $output = null): ?string
    {
        $response = Http::asForm()->timeout(15)->post(self::TOKEN_URL, [
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (! $response->successful()) {
            Log::warning('ContractsFinder auth failed: ' . $response->status());
            if ($output) $output->line("     [CF] Auth failed: HTTP " . $response->status());
            return null;
        }

        return $response->json('access_token');
    }

    private function searchNotices(string $token, string $term, string $fromDate, $output = null): array
    {
        $payload = [
            'searchTerm'    => $term,
            'publishedFrom' => $fromDate,
            'publishedTo'   => now()->format('Y-m-d'),
            'noticeTypes'   => ['Contract Notice', 'Prior Information Notice'],
            'sortBy'        => 'PublishedDate',
            'descending'    => true,
            'size'          => 50,
            'from'          => 0,
        ];

        $response = Http::timeout(30)
            ->withToken($token)
            ->asJson()
            ->post(self::SEARCH_URL, $payload);

        if ($output) {
            $output->line("     [CF] '{$term}' → HTTP " . $response->status());
            if (! $response->successful()) {
                $output->line("     [CF] Body: " . substr($response->body(), 0, 200));
            }
        }

        if (! $response->successful()) {
            Log::warning('ContractsFinder API error ' . $response->status());
            return [];
        }

        $body    = $response->json();
        $notices = $body['notices'] ?? $body['data'] ?? $body['results'] ?? [];

        if ($output) $output->line("     [CF] '{$term}': " . count($notices) . " notices");

        return $notices;
    }

    private function normalise(array $notice): array
    {
        $value = $notice['EstimatedValue'] ?? null;

        return [
            'source'         => 'contracts_finder',
            'source_ref'     => 'cf_' . ($notice['Id'] ?? uniqid()),
            'title'          => $notice['Title'] ?? 'Untitled',
            'description'    => $notice['Description'] ?? null,
            'buyer_name'     => $notice['OrganisationName'] ?? null,
            'buyer_location' => $notice['PostcodeArea'] ?? null,
            'value_low'      => null,
            'value_high'     => $value ? (int) $value : null,
            'published_at'   => isset($notice['PublishedDate'])
                ? substr($notice['PublishedDate'], 0, 10)
                : null,
            'deadline_at'    => $notice['CloseDate'] ?? $notice['DeadlineDate'] ?? null,
            'contract_start' => null,
            'contract_end'   => null,
            'source_url'     => $notice['NoticeUrl'] ?? null,
            'cpv_codes'      => $notice['CpvCodes'] ?? [],
        ];
    }
}
