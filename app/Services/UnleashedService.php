<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UnleashedService
{
    private const BASE_URL = 'https://api.unleashedsoftware.com';

    public function __construct(
        private readonly string $apiId,
        private readonly string $apiKey
    ) {}

    private function sign(string $queryString): string
    {
        return base64_encode(hash_hmac('sha256', $queryString, $this->apiKey, true));
    }

    private function headers(string $queryString = ''): array
    {
        return [
            'api-auth-id'        => $this->apiId,
            'api-auth-signature' => $this->sign($queryString),
            'Accept'             => 'application/json',
            'Content-Type'       => 'application/json',
        ];
    }

    public function get(string $endpoint, array $params = []): array
    {
        $queryString = http_build_query($params);
        $url = self::BASE_URL . '/' . $endpoint . ($queryString ? "?{$queryString}" : '');

        $response = Http::timeout(30)
            ->withHeaders($this->headers($queryString))
            ->get($url);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Unleashed API error ({$response->status()}): " . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    public function paginate(string $endpoint, array $params = []): array
    {
        $items = [];
        $page  = 1;

        do {
            $data  = $this->get($endpoint, array_merge($params, [
                'pageSize'   => 200,
                'pageNumber' => $page,
            ]));
            $items    = array_merge($items, $data['Items'] ?? []);
            $maxPages = $data['Pagination']['NumberOfPages'] ?? 1;
            $page++;
        } while ($page <= $maxPages);

        return $items;
    }
}
