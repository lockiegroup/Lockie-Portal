<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TenderAIService
{
    private const MODEL = 'claude-haiku-4-5-20251001';

    private const PRODUCT_CONTEXT = <<<'TEXT'
Lockie Group is a UK manufacturer specialising in:
- Security seals (pull-tight plastic seals, bolt seals, cable seals, metal seals)
- Tamper-evident seals and tags
- Numbered/serialised security seals for identification/audit trails
- Cable ties (standard, printed with text/logos, numbered/serialised)
- Security tags for retail and asset protection
- Container and cage seals for logistics/transport security
- Waste and recycling seals
- Postal seals for letter boxes and mail security
- Utility and meter seals (gas, electric, water meters)
- Bespoke printed/serialised identification products

IMPORTANT — these are NOT Lockie products (ignore/reject):
- CCTV systems, cameras or surveillance equipment
- Security guarding, manned security services
- Electronic access control systems, door entry
- Alarm systems or intruder detection
- IT security or cybersecurity
- Fencing or physical barriers
- Uniforms or workwear (unless specifically seal-related)
- General stationery or office supplies
TEXT;

    public function score(array $tender): array
    {
        $apiKey = config('services.anthropic.key');
        if (! $apiKey) {
            Log::warning('TenderAIService: No Anthropic API key configured. Skipping AI scoring.');
            return $this->fallback();
        }

        $prompt = $this->buildPrompt($tender);

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => self::MODEL,
                'max_tokens' => 400,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('TenderAIService API error: ' . $response->status() . ' ' . $response->body());
                return $this->fallback();
            }

            $text = $response->json('content.0.text') ?? '';
            return $this->parse($text);
        } catch (\Throwable $e) {
            Log::warning('TenderAIService exception: ' . $e->getMessage());
            return $this->fallback();
        }
    }

    private function buildPrompt(array $tender): string
    {
        $title    = $tender['title'] ?? 'Unknown';
        $buyer    = $tender['buyer_name'] ?? 'Unknown buyer';
        $desc     = mb_substr($tender['description'] ?? 'No description', 0, 800);
        $value    = $tender['value_high'] ? '£' . number_format((int)$tender['value_high']) : 'Not specified';
        $cpv      = implode(', ', (array)($tender['cpv_codes'] ?? [])) ?: 'None';
        $context  = self::PRODUCT_CONTEXT;

        return <<<PROMPT
{$context}

Assess this UK procurement opportunity and return ONLY a JSON object (no other text):

Title: {$title}
Buyer: {$buyer}
Estimated value: {$value}
CPV codes: {$cpv}
Description: {$desc}

Return this exact JSON structure:
{
  "relevance": "high" | "relevant" | "possible" | "not_relevant",
  "score": <integer 0-100>,
  "reasoning": "<one or two sentences explaining the match or lack thereof>",
  "products": ["<matched Lockie product category>", ...]
}

Scoring guide:
- 85-100 (high): Tender clearly describes products Lockie manufactures
- 60-84 (relevant): Strong indication Lockie could supply
- 35-59 (possible): Partial match or needs more information
- 0-34 (not_relevant): Not suitable for Lockie
PROMPT;
    }

    private function parse(string $text): array
    {
        // Extract JSON from response (model may add surrounding text)
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $data = json_decode($m[0], true);
            if ($data && isset($data['relevance'], $data['score'])) {
                return [
                    'ai_relevance' => in_array($data['relevance'], ['high', 'relevant', 'possible', 'not_relevant'])
                        ? $data['relevance']
                        : 'possible',
                    'ai_score'     => max(0, min(100, (int)$data['score'])),
                    'ai_reasoning' => $data['reasoning'] ?? null,
                    'ai_products'  => $data['products'] ?? [],
                ];
            }
        }

        Log::warning('TenderAIService: Failed to parse response: ' . $text);
        return $this->fallback();
    }

    private function fallback(): array
    {
        return [
            'ai_relevance' => 'possible',
            'ai_score'     => 0,
            'ai_reasoning' => 'AI scoring unavailable.',
            'ai_products'  => [],
        ];
    }
}
