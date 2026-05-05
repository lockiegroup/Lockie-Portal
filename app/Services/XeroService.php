<?php

namespace App\Services;

use App\Models\XeroToken;
use Illuminate\Support\Facades\Http;

class XeroService
{
    private const TOKEN_URL = 'https://identity.xero.com/connect/token';
    private const API_BASE  = 'https://api.xero.com/api.xro/2.0';

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

    public function postBankTransaction(array $settlementData): array
    {
        $token = $this->getValidToken();

        $lineItems = array_map(fn($line) => [
            'Description' => $line['description'],
            'Quantity'    => 1,
            'UnitAmount'  => $line['amount_net'],
            'AccountCode' => $line['account_code'],
            'TaxType'     => $line['tax_type'],
        ], $settlementData['lines']);

        $payload = [
            'BankTransactions' => [[
                'Type'            => 'RECEIVE',
                'BankAccount'     => config('services.xero.clearing_account_id')
                    ? ['AccountID' => config('services.xero.clearing_account_id')]
                    : ['Code'      => config('services.xero.clearing_account_code')],
                'Date'            => $settlementData['date'],
                'Reference'       => 'Amazon Settlement ' . $settlementData['settlement_id'],
                'LineAmountTypes' => 'EXCLUSIVE',
                'Contact'         => ['Name' => 'Amazon'],
                'LineItems'       => $lineItems,
            ]],
        ];

        try {
            $response = Http::retry(3, 1000, null, false)
                ->withToken($token->access_token)
                ->withHeaders(['Xero-Tenant-Id' => $token->tenant_id])
                ->acceptJson()
                ->post(self::API_BASE . '/BankTransactions', $payload);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $response = $e->response;
            $body = $response->json() ?? [];
            $errors = [];
            foreach ($body['Elements'] ?? [] as $element) {
                foreach ($element['ValidationErrors'] ?? [] as $err) {
                    if (!empty($err['Message'])) $errors[] = $err['Message'];
                }
            }
            throw new \RuntimeException(
                'Xero validation: ' . ($errors ? implode('; ', $errors) : $response->body())
            );
        }

        if ($response->failed()) {
            throw new \RuntimeException('Xero error (' . $response->status() . '): ' . $response->body());
        }

        $transaction = ($response->json('BankTransactions') ?? [])[0] ?? [];

        // Xero returns 200 but with a StatusAttributeString of ERROR on validation failure
        if (($transaction['StatusAttributeString'] ?? '') === 'ERROR') {
            $errors = [];
            foreach ($transaction['ValidationErrors'] ?? [] as $err) {
                $errors[] = $err['Message'] ?? '';
            }
            throw new \RuntimeException(
                'Xero validation error: ' . implode('; ', array_filter($errors))
            );
        }

        return $transaction;
    }

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
