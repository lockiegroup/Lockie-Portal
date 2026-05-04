<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SequentialFinderController extends Controller
{
    private UnleashedService $unleashed;

    public function __construct()
    {
        $this->unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key')
        );
    }

    public function index()
    {
        return view('sequential-finder.index');
    }

    public function search(Request $request): JsonResponse
    {
        $productCode = strtoupper(trim($request->input('product', '')));
        if (!$productCode) {
            return response()->json(['error' => 'Product code required'], 422);
        }

        $results = [];
        $seen    = [];

        // 1. Portal print_jobs (active + archived) — fastest, no API call needed
        PrintJob::withoutGlobalScopes()
            ->where('product_code', $productCode)
            ->whereNotNull('line_comment')
            ->get(['order_number', 'line_comment', 'order_date'])
            ->each(function ($job) use (&$results, &$seen) {
                $key   = $job->order_number;
                $range = $this->parseNumbered($job->line_comment);
                if (!$range || isset($seen[$key])) return;
                $seen[$key] = true;
                $results[]  = $range + [
                    'source'  => $key,
                    'type'    => str_starts_with($key, 'ASM-') ? 'Assembly' : 'Sales Order',
                    'date'    => $job->order_date?->format('Y-m-d'),
                    'comment' => $job->line_comment,
                ];
            });

        // 2. Unleashed Assemblies (all statuses, not just active)
        try {
            $assemblies = $this->unleashed->paginateFast('Assemblies', ['productCode' => $productCode], 200);
            foreach ($assemblies as $asm) {
                $num   = $asm['AssemblyNumber'] ?? null;
                $range = $this->parseNumbered($asm['Comments'] ?? '');
                if (!$num || !$range || isset($seen[$num])) continue;
                $seen[$num] = true;
                $results[]  = $range + [
                    'source'  => $num,
                    'type'    => 'Assembly',
                    'date'    => $this->unleashed->parseDate($asm['AssemblyDate'] ?? $asm['AssembleBy'] ?? null),
                    'comment' => $asm['Comments'] ?? '',
                ];
            }
        } catch (\Throwable) {}

        // 3. Unleashed Purchase Orders (China orders)
        try {
            $pos = $this->unleashed->paginateFast('PurchaseOrders', ['productCode' => $productCode], 200);
            foreach ($pos as $po) {
                $poNum = $po['OrderNumber'] ?? null;
                if (!$poNum || isset($seen[$poNum])) continue;
                foreach ($po['PurchaseOrderLines'] ?? [] as $line) {
                    if (strtoupper($line['Product']['ProductCode'] ?? '') !== $productCode) continue;
                    $comments = $line['Comments'] ?? '';
                    $range    = $this->parseNumbered($comments);
                    if (!$range) continue;
                    $seen[$poNum] = true;
                    $results[]    = $range + [
                        'source'  => $poNum,
                        'type'    => 'Purchase Order',
                        'date'    => $this->unleashed->parseDate($po['OrderDate'] ?? null),
                        'comment' => $comments,
                    ];
                    break;
                }
            }
        } catch (\Throwable) {}

        usort($results, fn($a, $b) => $b['to'] <=> $a['to']);

        $maxEnd = $results[0]['to'] ?? null;

        return response()->json([
            'product_code' => $productCode,
            'results'      => $results,
            'max_end'      => $maxEnd,
            'next_start'   => $maxEnd !== null ? $maxEnd + 1 : null,
        ]);
    }

    private function parseNumbered(string $text): ?array
    {
        if (preg_match('/Numbered:\s*(\d+)\s*[-–—]\s*(\d+)/i', $text, $m)) {
            return ['from' => (int) $m[1], 'to' => (int) $m[2]];
        }
        return null;
    }
}
