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
use Illuminate\Support\Facades\Gate;
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
            'scope'         => 'offline_access accounting.settings',
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

    public function settlementCsv(AmazonSettlement $settlement): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $settlement->load('lines');

        $date = $settlement->end_date?->format('d/m/Y') ?? now()->format('d/m/Y');

        $sellerFeeTypes = ['ReferralFeeToAmazon', 'FixedClosingFee', 'VariableClosingFee', 'Commission'];
        $fbaFeeTypes    = ['FBAPerUnitFulfillmentFee', 'FBAPerOrderFulfillmentFee', 'FBAWeightBasedFee', 'FBATransactionFee'];

        $payments     = $settlement->lines->whereIn('account_code', ['4000', '4001', '4002'])->sum('amount_gross');
        $sellerFees   = $settlement->lines->filter(fn($l) => in_array($l->product_type, $sellerFeeTypes))->sum('amount_gross');
        $fbaFees      = $settlement->lines->filter(fn($l) => in_array($l->product_type, $fbaFeeTypes))->sum('amount_gross');
        $subscription = $settlement->lines->where('account_code', '513')
                            ->filter(fn($l) => !in_array($l->product_type, array_merge($sellerFeeTypes, $fbaFeeTypes)))
                            ->sum('amount_gross');
        $advertising  = $settlement->lines->where('account_code', '502')->sum('amount_gross');
        $other        = $settlement->lines->where('account_code', '999')->sum('amount_gross');
        $transfer     = -(float) $settlement->deposit_amount;

        $rows = [
            ['description' => 'Payments',                      'amount' => $payments],
            ['description' => 'Seller Fees',                   'amount' => $sellerFees],
            ['description' => 'FBA Fees',                      'amount' => $fbaFees],
            ['description' => 'Subscription',                  'amount' => $subscription],
            ['description' => 'Advertising',                   'amount' => $advertising],
            ['description' => 'Other',                         'amount' => $other],
            ['description' => 'Transfer to Bank of Scotland',  'amount' => $transfer],
        ];

        $filename = 'amazon-settlement-' . $settlement->settlement_id . '.csv';

        return response()->streamDownload(function () use ($rows, $date) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Amount', 'Description', 'Reference']);

            foreach ($rows as $row) {
                if (round($row['amount'], 2) == 0) continue;
                fputcsv($out, [$date, round($row['amount'], 2), $row['description'], '']);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function xeroPost(AmazonSettlement $settlement): JsonResponse
    {
        try {
            Gate::authorize('amazon-admin');

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
