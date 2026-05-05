<?php

namespace App\Http\Controllers;

use App\Models\AmazonProfitSnapshot;
use App\Models\AmazonSettlement;
use App\Models\XeroToken;
use App\Services\AmazonService;
use App\Services\AmazonSyncService;
use App\Services\UnleashedService;
use App\Services\XeroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AmazonController extends Controller
{
    public function index(): View
    {
        $totalSettlements = AmazonSettlement::count();
        $pendingXero      = AmazonSettlement::where('status', 'pending')->count();
        $lastSync         = AmazonSettlement::max('processed_at');
        $hasXeroToken     = XeroToken::exists();

        return view('amazon.index', compact(
            'totalSettlements', 'pendingXero', 'lastSync', 'hasXeroToken'
        ));
    }

    public function sync(Request $request): JsonResponse
    {
        try {
            $service = new AmazonSyncService(
                new AmazonService(),
                new XeroService(),
                new UnleashedService(
                    config('services.unleashed.id'),
                    config('services.unleashed.key')
                )
            );

            $result = $service->syncSettlements();

            return response()->json([
                'success' => true,
                'message' => "Imported {$result['imported']}, skipped {$result['skipped']}, errors {$result['errors']}.",
                'result'  => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function settlements(): JsonResponse
    {
        $settlements = AmazonSettlement::latest('end_date')
            ->paginate(25)
            ->through(fn($s) => [
                'id'             => $s->id,
                'settlement_id'  => $s->settlement_id,
                'start_date'     => $s->start_date->toDateString(),
                'end_date'       => $s->end_date->toDateString(),
                'deposit_amount' => $s->deposit_amount,
                'currency'       => $s->currency,
                'status'         => $s->status,
                'processed_at'   => $s->processed_at?->toDateTimeString(),
            ]);

        return response()->json($settlements);
    }

    public function settlementDetail(AmazonSettlement $settlement): JsonResponse
    {
        $settlement->load('lines');

        $vatSummary = $settlement->lines
            ->groupBy('account_code')
            ->map(fn($lines) => [
                'account_code' => $lines->first()->account_code,
                'gross'        => $lines->sum('amount_gross'),
                'net'          => $lines->sum('amount_net'),
                'vat'          => $lines->sum('vat_amount'),
                'count'        => $lines->count(),
            ])
            ->values();

        return response()->json([
            'settlement'  => $settlement,
            'lines'       => $settlement->lines,
            'vat_summary' => $vatSummary,
        ]);
    }

    public function profitReport(Request $request): JsonResponse
    {
        $query = AmazonProfitSnapshot::query();

        if ($request->filled('start')) {
            $query->where('period_start', '>=', $request->input('start'));
        }

        if ($request->filled('end')) {
            $query->where('period_end', '<=', $request->input('end'));
        }

        if ($request->filled('channel')) {
            $query->where('fulfillment_channel', $request->input('channel'));
        }

        if ($request->filled('product_type')) {
            $query->where('product_type', $request->input('product_type'));
        }

        $snapshots = $query->orderBy('period_end', 'desc')->get();

        $summary = [
            'gross_sales'  => $snapshots->sum('gross_sales'),
            'gross_profit' => $snapshots->sum('gross_profit'),
            'ad_spend_net' => $snapshots->sum('ad_spend_net'),
            'margin_pct'   => $snapshots->sum('gross_sales') > 0
                ? round($snapshots->sum('gross_profit') / $snapshots->sum('gross_sales') * 100, 2)
                : 0,
            'roas'         => $snapshots->sum('ad_spend_net') > 0
                ? round($snapshots->sum('gross_sales') / $snapshots->sum('ad_spend_net'), 2)
                : 0,
        ];

        return response()->json(['snapshots' => $snapshots, 'summary' => $summary]);
    }

    public function xeroConnect(): RedirectResponse
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id'     => config('services.xero.client_id'),
            'redirect_uri'  => route('amazon.xero.callback'),
            'scope'         => 'offline_access accounting.transactions',
            'state'         => csrf_token(),
        ]);

        return redirect('https://login.xero.com/identity/connect/authorize?' . $params);
    }

    public function xeroCallback(Request $request): RedirectResponse
    {
        $code = $request->input('code');

        if (!$code) {
            return redirect()->route('amazon.index')->with('error', 'Xero authorisation failed.');
        }

        try {
            (new XeroService())->handleOAuthCallback($code);
        } catch (\Throwable $e) {
            return redirect()->route('amazon.index')->with('error', 'Xero connection failed: ' . $e->getMessage());
        }

        return redirect()->route('amazon.index')->with('success', 'Xero connected successfully.');
    }

    public function xeroPost(AmazonSettlement $settlement): JsonResponse
    {
        $this->authorize('amazon-admin');

        try {
            $service = new AmazonSyncService(
                new AmazonService(),
                new XeroService(),
                new UnleashedService(
                    config('services.unleashed.id'),
                    config('services.unleashed.key')
                )
            );

            $service->postToXero($settlement);

            return response()->json([
                'success' => true,
                'status'  => $settlement->fresh()->status,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
