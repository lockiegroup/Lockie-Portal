<?php

namespace App\Services;

use App\Models\XeroToken;
use Illuminate\Support\Facades\Http;

class XeroService
{
    private const TOKEN_URL      = 'https://identity.xero.com/connect/token';
    private const API_BASE       = 'https://api.xero.com/api.xro/2.0';
    private const BANKFEEDS_BASE = 'https://api.xero.com/bankfeeds.xro/1.0';

    public function getValidToken(): XeroToken
    {
        $token = XeroToken::latest('id')->firstOrFail();

        if ($token->expires_at->subMinutes(5)->isPast()) {
            $token = $this->refreshToken($token);
        }

        return $token;
    }

    public function refreshToken(XeroToken $token): XeroToken
    {
        $response = Http::retry(3, 1000)
            ->withBasicAuth(config('services.xero.client_id'), config('services.xero.client_secret'))
            ->asForm()
            ->post(self::TOKEN_URL, [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $token->refresh_token,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Xero token refresh error (' . $response->status() . '): ' . $response->body()
            );
        }

        $data = $response->json();

        $token->update([
            'access_token'  => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_at'    => now()->addSeconds($data['expires_in']),
        ]);

        return $token->fresh();
    }

    // -------------------------------------------------------------------------
    // Bank Feeds API
    // -------------------------------------------------------------------------

    /**
     * Returns the feed connection ID for the AMAZON clearing account,
     * creating one if it doesn't exist yet.
     */
    public function getOrCreateFeedConnection(): string
    {
        $token = $this->getValidToken();

        if (!empty($token->feed_connection_id)) {
            return $token->feed_connection_id;
        }

        // Check for existing active connection
        $response = Http::retry(3, 1000)
            ->withToken($token->access_token)
            ->withHeaders(['Xero-Tenant-Id' => $token->tenant_id])
            ->acceptJson()
            ->get(self::BANKFEEDS_BASE . '/FeedConnections');

        if ($response->ok()) {
            foreach ($response->json('items') ?? [] as $conn) {
                $status = $conn['status'] ?? '';
                if (in_array($status, ['ACTIVE', 'PENDING'], true)) {
                    $id = $conn['id'];
                    $token->update(['feed_connection_id' => $id]);
                    return $id;
                }
            }
        }

        // Create a new feed connection linked to the existing AMAZON bank account
        $accountId = config('services.xero.clearing_account_id');

        $item = [
            'accountToken' => 'amazon-settlement-feed',
            'accountType'  => 'BANK',
            'currency'     => 'GBP',
            'country'      => 'GB',
        ];

        if ($accountId) {
            $item['accountId'] = $accountId;
        }

        $response = Http::retry(3, 1000)
            ->withToken($token->access_token)
            ->withHeaders(['Xero-Tenant-Id' => $token->tenant_id])
            ->acceptJson()
            ->post(self::BANKFEEDS_BASE . '/FeedConnections', ['items' => [$item]]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Failed to create Xero feed connection (' . $response->status() . '): ' . $response->body()
            );
        }

        $items = $response->json('items') ?? [];
        $id    = $items[0]['id'] ?? null;

        if (!$id) {
            throw new \RuntimeException('No feed connection ID returned from Xero: ' . $response->body());
        }

        $token->update(['feed_connection_id' => $id]);

        return $id;
    }

    /**
     * Post a bank-feed statement (for Xero "Reconcile" tab).
     *
     * $statementData = [
     *   'start_date' => 'YYYY-MM-DD',
     *   'end_date'   => 'YYYY-MM-DD',
     *   'lines'      => [
     *     [
     *       'postedDate'           => 'YYYY-MM-DD',
     *       'description'          => string,
     *       'amount'               => float (always positive),
     *       'creditDebitIndicator' => 'CREDIT'|'DEBIT',
     *       'transactionId'        => string (unique),
     *     ],
     *     ...
     *   ],
     * ]
     */
    public function postBankFeedStatement(array $statementData): array
    {
        $token            = $this->getValidToken();
        $feedConnectionId = $this->getOrCreateFeedConnection();

        $payload = [
            'items' => [[
                'feedConnectionId' => $feedConnectionId,
                'startDate'        => $statementData['start_date'],
                'endDate'          => $statementData['end_date'],
                'statementLines'   => $statementData['lines'],
            ]],
        ];

        try {
            $response = Http::retry(3, 1000, null, false)
                ->withToken($token->access_token)
                ->withHeaders(['Xero-Tenant-Id' => $token->tenant_id])
                ->acceptJson()
                ->post(self::BANKFEEDS_BASE . '/Statements', $payload);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new \RuntimeException('Xero Bank Feeds request failed: ' . $e->getMessage());
        }

        if ($response->failed()) {
            throw new \RuntimeException(
                'Xero Bank Feeds error (' . $response->status() . '): ' . $response->body()
            );
        }

        $items = $response->json('items') ?? [];

        // Surface any per-statement validation errors
        foreach ($items as $item) {
            if (!empty($item['errors'])) {
                $msgs = array_column($item['errors'], 'detail');
                throw new \RuntimeException(
                    'Xero Bank Feeds validation: ' . implode('; ', array_filter($msgs))
                );
            }
        }

        return $items[0] ?? [];
    }

    // -------------------------------------------------------------------------
    // Accounting API helpers (kept for reference / future use)
    // -------------------------------------------------------------------------

    public function getAccounts(): array
    {
        $token = $this->getValidToken();

        $response = Http::retry(3, 1000)
            ->withToken($token->access_token)
            ->withHeaders(['Xero-Tenant-Id' => $token->tenant_id])
            ->acceptJson()
            ->get(self::API_BASE . '/Accounts');

        if ($response->failed()) {
            throw new \RuntimeException(
                'Xero accounts error (' . $response->status() . '): ' . $response->body()
            );
        }

        return $response->json('Accounts') ?? [];
    }

    public function handleOAuthCallback(string $code): XeroToken
    {
        $response = Http::retry(3, 1000)
            ->withBasicAuth(config('services.xero.client_id'), config('services.xero.client_secret'))
            ->asForm()
            ->post(self::TOKEN_URL, [
                'grant_type'   => 'authorization_code',
                'code'         => $code,
                'redirect_uri' => route('amazon.xero.callback'),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Xero OAuth callback error (' . $response->status() . '): ' . $response->body()
            );
        }

        $data = $response->json();

        $tenantsResponse = Http::withToken($data['access_token'])
            ->get('https://api.xero.com/connections');

        $tenant = ($tenantsResponse->json() ?? [])[0] ?? [];

        return XeroToken::updateOrCreate(
            ['tenant_id' => $tenant['tenantId'] ?? 'default'],
            [
                'tenant_name'   => $tenant['tenantName'] ?? 'Xero',
                'access_token'  => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at'    => now()->addSeconds($data['expires_in']),
            ]
        );
    }
}
