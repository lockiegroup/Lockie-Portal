<?php

namespace App\Console\Commands;

use App\Services\UnleashedService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugAssemblies extends Command
{
    protected $signature   = 'debug:assemblies';
    protected $description = 'Dump raw Unleashed Assemblies API response';

    public function handle(): void
    {
        $id  = config('services.unleashed.id');
        $key = config('services.unleashed.key');

        $qs  = http_build_query(['pageSize' => 10, 'pageNumber' => 1]);
        $sig = base64_encode(hash_hmac('sha256', $qs, $key, true));
        $url = 'https://api.unleashedsoftware.com/Assemblies?' . $qs;

        $this->line("GET $url");
        $this->line('');

        $response = Http::timeout(30)->withHeaders([
            'api-auth-id'        => $id,
            'api-auth-signature' => $sig,
            'Accept'             => 'application/json',
            'Content-Type'       => 'application/json',
        ])->get($url);

        $this->line('Status: ' . $response->status());
        $this->line('');
        $this->line('Body:');
        $this->line($response->body());
    }
}
