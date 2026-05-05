<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AmazonService
{
    private const SP_API_BASE  = 'https://sellingpartnerapi-eu.amazon.com';
    private const ADS_API_BASE = 'https://advertising-api-eu.amazon.com';
    private const LWA_URL      = 'https://api.amazon.com/auth/o2/token';

    public function getAccessToken(): string
    {
        // Bust any stale null cached from a previous failed attempt
        if (Cache::has('amazon_sp_access_token') && !Cache::get('amazon_sp_access_token')) {
            Cache::forget('amazon_sp_access_token');
        }

        return Cache::remember('amazon_sp_access_token', 55 * 60, function () {
            $refreshToken = config('services.amazon.refresh_token');
            $clientId     = config('services.amazon.client_id');
            $clientSecret = config('services.amazon.client_secret');

            if (!$refreshToken || !$clientId || !$clientSecret) {
                throw new \RuntimeException(
                    'Amazon SP-API credentials are not configured. Check AMAZON_CLIENT_ID, AMAZON_CLIENT_SECRET and AMAZON_REFRESH_TOKEN in .env.'
                );
            }

            $response = Http::retry(3, 1000)
                ->asForm()
                ->post(self::LWA_URL, [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->failed()) {
                throw new \RuntimeException(
                    'Amazon LWA token error (' . $response->status() . '): ' . $response->body()
                );
            }

            $token = $response->json('access_token');
            if (!$token) {
                throw new \RuntimeException(
                    'Amazon LWA response missing access_token. Response: ' . $response->body()
                );
            }

            return $token;
        });
    }

    public function getAdsAccessToken(): string
    {
        if (Cache::has('amazon_ads_access_token') && !Cache::get('amazon_ads_access_token')) {
            Cache::forget('amazon_ads_access_token');
        }

        return Cache::remember('amazon_ads_access_token', 55 * 60, function () {
            $refreshToken = config('services.amazon_ads.refresh_token');
            $clientId     = config('services.amazon_ads.client_id');
            $clientSecret = config('services.amazon_ads.client_secret');

            if (!$refreshToken || !$clientId || !$clientSecret) {
                throw new \RuntimeException(
                    'Amazon Ads API credentials are not configured. Check AMAZON_ADS_* keys in .env.'
                );
            }

            $response = Http::retry(3, 1000)
                ->asForm()
                ->post(self::LWA_URL, [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->failed()) {
                throw new \RuntimeException(
                    'Amazon Ads LWA token error (' . $response->status() . '): ' . $response->body()
                );
            }

            $token = $response->json('access_token');
            if (!$token) {
                throw new \RuntimeException(
                    'Amazon Ads LWA response missing access_token. Response: ' . $response->body()
                );
            }

            return $token;
        });
    }

    /**
     * List available DONE settlement reports from SP-API.
     * Deduplication against the DB is handled by AmazonSyncService after TSV parsing,
     * since the Amazon settlement-id is only known once the file is downloaded.
     *
     * NOTE: SP-API production endpoints require AWS SigV4 signing in addition to the
     * LWA bearer token. Add a SigV4 signing layer if requests return 403 in production.
     */
    public function getSettlementReports(): array
    {
        $token = $this->getAccessToken();

        $response = Http::retry(3, 1000, fn($e) => $e->response?->status() === 429, false)
            ->withHeaders([
                'x-amz-access-token'    => $token,
                'x-amzn-marketplace-id' => config('services.amazon.marketplace_id'),
            ])
            ->get(self::SP_API_BASE . '/reports/2021-06-30/reports', [
                'reportTypes'        => 'GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE',
                'processingStatuses' => 'DONE',
                'pageSize'           => 100,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Amazon SP-API reports error (' . $response->status() . '): ' . $response->body()
            );
        }

        return $response->json('reports') ?? [];
    }

    /**
     * Fetch the settlement flat-file TSV for a given reportDocumentId.
     * Returns an array of rows, each keyed by TSV column header.
     */
    public function downloadSettlementReport(string $reportDocumentId): array
    {
        $token = $this->getAccessToken();

        $docResponse = Http::retry(3, 1000, fn($e) => $e->response?->status() === 429, false)
            ->withHeaders(['x-amz-access-token' => $token])
            ->get(self::SP_API_BASE . '/reports/2021-06-30/documents/' . $reportDocumentId);

        if ($docResponse->failed()) {
            throw new \RuntimeException(
                'Amazon document fetch error (' . $docResponse->status() . '): ' . $docResponse->body()
            );
        }

        $url = $docResponse->json('url');
        if (!$url) {
            throw new \RuntimeException('Amazon document URL missing for: ' . $reportDocumentId);
        }

        // Presigned S3 URL — no auth header required
        $tsvResponse = Http::retry(3, 1000)->timeout(120)->get($url);

        if ($tsvResponse->failed()) {
            throw new \RuntimeException('Amazon TSV download error: ' . $tsvResponse->status());
        }

        return $this->parseTsv($tsvResponse->body());
    }

    private function parseTsv(string $content): array
    {
        $lines = explode("\n", str_replace("\r\n", "\n", trim($content)));
        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines), "\t");
        $rows    = [];

        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $values = str_getcsv($line, "\t");
            while (count($values) < count($headers)) {
                $values[] = '';
            }
            $rows[] = array_combine($headers, $values);
        }

        return $rows;
    }

    /**
     * Request and download a Sponsored Products campaigns report from the Advertising API.
     * Returns an array of campaign objects with spend, impressions, clicks, sales14d.
     */
    public function getAdvertisingReport(string $startDate, string $endDate): array
    {
        $token     = $this->getAdsAccessToken();
        $profileId = config('services.amazon_ads.profile_id');

        $headers = [
            'Amazon-Advertising-API-ClientId' => config('services.amazon_ads.client_id'),
            'Amazon-Advertising-API-Scope'    => $profileId,
        ];

        $createResponse = Http::retry(3, 1000, fn($e) => $e->response?->status() === 429, false)
            ->withToken($token)
            ->withHeaders($headers)
            ->post(self::ADS_API_BASE . '/v3/reports', [
                'name'          => "SP Campaigns {$startDate} to {$endDate}",
                'startDate'     => $startDate,
                'endDate'       => $endDate,
                'configuration' => [
                    'adProduct'    => 'SPONSORED_PRODUCTS',
                    'groupBy'      => ['campaign'],
                    'columns'      => ['campaignId', 'campaignName', 'spend', 'impressions', 'clicks', 'sales14d'],
                    'reportTypeId' => 'spCampaigns',
                    'timeUnit'     => 'SUMMARY',
                    'format'       => 'GZIP_JSON',
                ],
            ]);

        if ($createResponse->failed()) {
            throw new \RuntimeException(
                'Amazon Ads report create error (' . $createResponse->status() . '): ' . $createResponse->body()
            );
        }

        $reportId = $createResponse->json('reportId');
        if (!$reportId) {
            throw new \RuntimeException('Amazon Ads reportId missing from response.');
        }

        // Poll until complete — max 10 attempts × 5s = 50s
        $reportUrl = null;
        for ($i = 0; $i < 10; $i++) {
            sleep(5);

            $statusResponse = Http::retry(2, 1000, null, false)
                ->withToken($token)
                ->withHeaders($headers)
                ->get(self::ADS_API_BASE . '/v3/reports/' . $reportId);

            if ($statusResponse->failed()) continue;

            $status = $statusResponse->json('status');

            if ($status === 'COMPLETED') {
                $reportUrl = $statusResponse->json('url');
                break;
            }

            if ($status === 'FAILED') {
                throw new \RuntimeException('Amazon Ads report generation failed.');
            }
        }

        if (!$reportUrl) {
            throw new \RuntimeException('Amazon Ads report timed out waiting for completion.');
        }

        $download = Http::retry(3, 1000)->timeout(120)->get($reportUrl);

        if ($download->failed()) {
            throw new \RuntimeException('Amazon Ads report download failed: ' . $download->status());
        }

        $decoded = gzdecode($download->body());

        return json_decode($decoded, true) ?? [];
    }
}
