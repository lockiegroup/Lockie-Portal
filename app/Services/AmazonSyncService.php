<?php

namespace App\Services;

use App\Models\AmazonProfitSnapshot;
use App\Models\AmazonSettlement;
use App\Models\AmazonSettlementLine;
use Illuminate\Support\Facades\Log;

class AmazonSyncService
{
    public function __construct(
        private readonly AmazonService    $amazon,
        private readonly XeroService      $xero,
        private readonly UnleashedService $unleashed
    ) {}

    public function syncSettlements(): array
    {
        $reports  = $this->amazon->getSettlementReports();
        $imported = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ($reports as $report) {
            $documentId = $report['reportDocumentId'] ?? null;
            if (!$documentId) continue;

            try {
                $rows = $this->amazon->downloadSettlementReport($documentId);

                if (empty($rows)) {
                    $skipped++;
                    continue;
                }

                // The settlement-id is the same on every data row
                $settlementId = $rows[0]['settlement-id'] ?? null;
                if (!$settlementId) {
                    $skipped++;
                    continue;
                }

                if (AmazonSettlement::where('settlement_id', $settlementId)->exists()) {
                    $skipped++;
                    continue;
                }

                $this->processSettlement($rows);
                $imported++;
            } catch (\Throwable $e) {
                Log::error('AmazonSyncService: settlement import failed', [
                    'documentId' => $documentId,
                    'error'      => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    public function processSettlement(array $rows): AmazonSettlement
    {
        $first = $rows[0];

        $settlement = AmazonSettlement::create([
            'settlement_id'  => $first['settlement-id'],
            'start_date'     => $this->parseDateStr($first['settlement-start-date'] ?? ''),
            'end_date'       => $this->parseDateStr($first['settlement-end-date'] ?? ''),
            'deposit_amount' => (float) ($first['total-amount'] ?? $first['deposit-amount'] ?? 0),
            'currency'       => 'GBP',
            'status'         => 'pending',
            'raw_data'       => $rows,
            'processed_at'   => now(),
        ]);

        $lines = [];
        $now   = now()->toDateTimeString();

        foreach ($rows as $row) {
            $classified = $this->classifyLine($row);
            if ($classified === null) continue;

            $lines[] = array_merge($classified, [
                'settlement_id' => $settlement->id,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        if (!empty($lines)) {
            foreach (array_chunk($lines, 100) as $chunk) {
                AmazonSettlementLine::insert($chunk);
            }
        }

        $this->calculateVat($settlement);

        return $settlement;
    }

    public function reprocessSettlement(AmazonSettlement $settlement): void
    {
        $raw = $settlement->raw_data;
        if (empty($raw)) return;

        $first = $raw[0];
        $settlement->update([
            'deposit_amount' => (float) ($first['total-amount'] ?? $first['deposit-amount'] ?? 0),
            'start_date'     => $this->parseDateStr($first['settlement-start-date'] ?? ''),
            'end_date'       => $this->parseDateStr($first['settlement-end-date'] ?? ''),
        ]);

        // Preserve any Unleashed SO numbers already looked up before deleting lines
        $soMap = $settlement->lines()
            ->whereNotNull('order_id')
            ->whereNotNull('unleashed_order_no')
            ->pluck('unleashed_order_no', 'order_id')
            ->all();

        $settlement->lines()->delete();

        $lines = [];
        $now   = now()->toDateTimeString();
        foreach ($raw as $row) {
            $classified = $this->classifyLine($row);
            if ($classified === null) continue;
            $orderId = $row['order-id'] ?: null;
            $lines[] = array_merge($classified, [
                'settlement_id'    => $settlement->id,
                'unleashed_order_no' => $orderId ? ($soMap[$orderId] ?? null) : null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
        foreach (array_chunk($lines, 100) as $chunk) {
            AmazonSettlementLine::insert($chunk);
        }

        $settlement->refresh();
        $this->calculateVat($settlement);

    }

    public function lookupUnleashedOrders(AmazonSettlement $settlement): int
    {
        $amazonIds = $settlement->lines()
            ->whereNotNull('order_id')
            ->whereNull('unleashed_order_no')
            ->pluck('order_id')
            ->unique()
            ->values()
            ->all();

        if (empty($amazonIds)) return 0;

        // Look back 90 days before settlement start — Amazon orders are placed
        // and fulfilled weeks before they appear in a settlement report.
        $start = ($settlement->start_date ?? now())->copy()->subDays(365)->toDateString();
        $end   = ($settlement->end_date   ?? now())->toDateString();

        $map   = $this->unleashed->fetchOrderNumbersByAmazonIds($amazonIds, $start, $end);
        $count = 0;

        foreach ($map as $amazonId => $unleashedNo) {
            $settlement->lines()
                ->where('order_id', $amazonId)
                ->update(['unleashed_order_no' => $unleashedNo]);
            $count++;
        }

        $unmatched = array_values(array_diff($amazonIds, array_keys($map)));
        \Log::info('lookupUnleashedOrders', [
            'sought'    => count($amazonIds),
            'matched'   => $count,
            'unmatched' => $unmatched,
        ]);

        return $count;
    }

    public function classifyLine(array $row): ?array
    {
        $txnType = $row['transaction-type'] ?? '';

        // Skip summary rows (no transaction type)
        if (empty($txnType)) return null;

        $channel = match($row['fulfillment-id'] ?? '') {
            'AFN'   => 'FBA',
            'MFN'   => 'FBM',
            default => null,
        };

        // Resolve amount and description from whichever column is populated
        [$amount, $amountDesc] = $this->resolveAmount($row);

        if ($amount === 0.0) return null;

        $accountCode = match(true) {
            // Other promotional discounts → fees
            in_array($amountDesc, [
                'RunLightningDeal', 'Promotion',
                'DiscountedLoyaltyPointsFee', 'FreeShipping',
            ], true)                                                                      => '513',
            // PromotionalRebate is Amazon's contribution to a promotion — treat as order revenue
            $amountDesc === 'PromotionalRebate' && $channel === 'FBA'                 => '4001',
            $amountDesc === 'PromotionalRebate'                                          => '4000',
            $amountDesc === 'Principal' && $channel === 'FBA'                          => '4001',
            $amountDesc === 'Principal'                                                   => '4000',
            // Marketplace facilitator tax and UK VAT — bundle into order gross total
            str_starts_with($amountDesc, 'MarketplaceFacilitatorTax') && $channel === 'FBA' => '4001',
            str_starts_with($amountDesc, 'MarketplaceFacilitatorTax')                   => '4000',
            str_starts_with($amountDesc, 'MarketplaceFacilitatorVAT') && $channel === 'FBA' => '4001',
            str_starts_with($amountDesc, 'MarketplaceFacilitatorVAT')                   => '4000',
            $amountDesc === 'Tax' && $channel === 'FBA'                                => '4001',
            $amountDesc === 'Tax'                                                         => '4000',
            in_array($amountDesc, ['ShippingTax', 'TaxDiscount'], true)                => '4002',
            // FBA / fulfilment fees
            in_array($amountDesc, ['FBAPerUnitFulfillmentFee', 'FBAPerOrderFulfillmentFee',
                                   'FBAWeightBasedFee', 'FBATransactionFee'], true)    => '513',
            // Referral / seller fees
            in_array($amountDesc, ['ReferralFeeToAmazon', 'FixedClosingFee',
                                   'VariableClosingFee', 'Commission',
                                   'RefundCommission'], true)                            => '513',
            // Digital services fee (small per-order charge)
            in_array($amountDesc, ['DigitalServicesFee', 'Digital Services Fee'], true) => '513',
            // Shipping revenue — include regardless of channel; FBA rows rarely have this but
            // FBM rows sometimes lack a fulfillment-id, causing channel to be null.
            in_array($amountDesc, ['Shipping', 'ShippingHB'], true)                   => '4002',
            $amountDesc === 'ShippingChargeback'                                         => '513',
            // Advertising — match by transaction type OR description (Amazon uses both)
            strcasecmp($txnType, 'advertising') === 0
                || stripos($amountDesc, 'advertising') !== false                        => '502',
            default                                                                        => '999',
        };

        return [
            'transaction_type'    => $txnType,
            'order_id'            => $row['order-id'] ?: null,
            'sku'                 => $row['sku'] ?: null,
            'product_type'        => $amountDesc ?: null,
            'fulfillment_channel' => $channel,
            'amount_gross'        => $amount,
            'amount_net'          => 0.0,
            'vat_amount'          => 0.0,
            'vat_rate'            => 0.00,
            'account_code'        => $accountCode,
            'posted_date'         => $this->parseDateStr($row['posted-date'] ?? ''),
        ];
    }

    /**
     * Amazon's flat file splits amounts across multiple typed columns.
     * Find the first populated column and return [amount, description].
     */
    private function resolveAmount(array $row): array
    {
        $candidates = [
            ['price-amount',        'price-type'],
            ['item-related-fee-amount', 'item-related-fee-type'],
            ['shipment-fee-amount', 'shipment-fee-type'],
            ['order-fee-amount',    'order-fee-type'],
            ['promotion-amount',    'promotion-type'],
            ['other-amount',        null],
            ['misc-fee-amount',     null],
            ['direct-payment-amount', 'direct-payment-type'],
        ];

        foreach ($candidates as [$amtCol, $typeCol]) {
            $val = $row[$amtCol] ?? '';
            if ($val !== '' && $val !== '0') {
                return [(float) $val, $typeCol ? ($row[$typeCol] ?? '') : ''];
            }
        }

        return [0.0, ''];
    }

    /**
     * Re-parse all existing settlements from stored raw_data using the current classifyLine logic.
     * Fixes settlements imported before the column mapping was corrected.
     */
    public function reprocessAllSettlements(): array
    {
        $settlements = AmazonSettlement::all();
        $count = 0;

        foreach ($settlements as $settlement) {
            $raw = $settlement->raw_data;
            if (empty($raw)) continue;

            $first = $raw[0];

            // Fix deposit amount
            $settlement->update([
                'deposit_amount' => (float) ($first['total-amount'] ?? $first['deposit-amount'] ?? 0),
                'start_date'     => $this->parseDateStr($first['settlement-start-date'] ?? ''),
                'end_date'       => $this->parseDateStr($first['settlement-end-date'] ?? ''),
            ]);

            // Delete old lines and re-insert
            $settlement->lines()->delete();

            $lines = [];
            $now   = now()->toDateTimeString();

            foreach ($raw as $row) {
                $classified = $this->classifyLine($row);
                if ($classified === null) continue;
                $lines[] = array_merge($classified, [
                    'settlement_id' => $settlement->id,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }

            foreach (array_chunk($lines, 100) as $chunk) {
                AmazonSettlementLine::insert($chunk);
            }

            $settlement->refresh();
            $this->calculateVat($settlement);
            $count++;
        }

        return ['reprocessed' => $count];
    }

    private function parseDateStr(string $value): ?string
    {
        if (!$value) return null;
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    public function calculateVat(AmazonSettlement $settlement): void
    {
        $settlement->lines()->each(function (AmazonSettlementLine $line) {
            $gross = (float) $line->amount_gross;

            if ($line->account_code === '502') {
                // Advertising: settlement TSV shows NET (ex-VAT); actual payout deduction is gross.
                // Keep amount_gross as the raw TSV net; vat_amount is the 20% addition on top.
                $vat = round($gross * 0.20, 4);
                $line->update(['amount_net' => round($gross, 4), 'vat_amount' => $vat, 'vat_rate' => 20.00]);
            } elseif ($line->account_code === '513') {
                // FBA / referral / subscription fees: settlement shows GROSS (VAT-inclusive)
                $net = round($gross / 1.20, 4);
                $vat = round($gross - $net, 4);
                $line->update(['amount_net' => $net, 'vat_amount' => $vat, 'vat_rate' => 20.00]);
            } else {
                // Sales: outside scope under Marketplace Facilitator rules
                $line->update(['amount_net' => $gross, 'vat_amount' => 0.0, 'vat_rate' => 0.00]);
            }
        });
    }

    public function syncAdSpend(string $startDate, string $endDate): array
    {
        $campaigns = $this->amazon->getAdvertisingReport($startDate, $endDate);
        $imported  = 0;

        $settlement = AmazonSettlement::firstOrCreate(
            ['settlement_id' => "ads-{$startDate}-{$endDate}"],
            [
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'deposit_amount' => 0,
                'currency'       => 'GBP',
                'status'         => 'pending',
                'processed_at'   => now(),
            ]
        );

        $now   = now()->toDateTimeString();
        $lines = [];

        foreach ($campaigns as $campaign) {
            $spend = (float) ($campaign['spend'] ?? 0);
            if ($spend <= 0) continue;

            // Advertising API returns net spend (ex-VAT); gross = net * 1.20
            $net = round($spend, 4);
            $vat = round($spend * 0.20, 4);

            $lines[] = [
                'settlement_id'       => $settlement->id,
                'transaction_type'    => 'advertising',
                'order_id'            => null,
                'sku'                 => null,
                'product_type'        => $campaign['campaignName'] ?? null,
                'fulfillment_channel' => null,
                'amount_gross'        => $spend,   // raw net from API; CSV grosses up × 1.20
                'amount_net'          => $net,
                'vat_amount'          => $vat,
                'vat_rate'            => 20.00,
                'account_code'        => '502',
                'created_at'          => $now,
                'updated_at'          => $now,
            ];

            $imported++;
        }

        foreach (array_chunk($lines, 100) as $chunk) {
            AmazonSettlementLine::insert($chunk);
        }

        return ['imported' => $imported, 'settlement_id' => $settlement->settlement_id];
    }

    public function buildProfitSnapshot(AmazonSettlement $settlement): void
    {
        // Fetch product costs from Unleashed keyed by ProductCode (SKU)
        $products   = $this->unleashed->fetchProducts();
        $cogsByCode = [];
        foreach ($products as $product) {
            $code = $product['ProductCode'] ?? null;
            if ($code) {
                $cogsByCode[$code] = (float) ($product['AverageLandedCost'] ?? 0);
            }
        }

        $groups = [];

        foreach ($settlement->lines as $line) {
            $channel = $line->fulfillment_channel ?? 'FBM';
            $type    = $line->product_type ?? 'Unknown';
            $key     = $channel . '|' . $type;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'fulfillment_channel' => $channel,
                    'product_type'        => $type,
                    'sku'                 => $line->sku,
                    'gross_sales'         => 0.0,
                    'returns'             => 0.0,
                    'referral_fees_net'   => 0.0,
                    'fba_fees_net'        => 0.0,
                    'ad_spend_net'        => 0.0,
                    'cogs'                => 0.0,
                ];
            }

            $net = (float) $line->amount_net;

            match($line->account_code) {
                '4000', '4001' => $net >= 0
                    ? $groups[$key]['gross_sales'] += $net
                    : $groups[$key]['returns']     += abs($net),
                '6101'         => $groups[$key]['referral_fees_net'] += abs($net),
                '6100'         => $groups[$key]['fba_fees_net']      += abs($net),
                '6102'         => $groups[$key]['ad_spend_net']      += abs($net),
                default        => null,
            };

            if ($line->sku && isset($cogsByCode[$line->sku])) {
                $groups[$key]['cogs'] += $cogsByCode[$line->sku];
            }
        }

        foreach ($groups as $data) {
            $profit = $data['gross_sales']
                - $data['returns']
                - $data['referral_fees_net']
                - $data['fba_fees_net']
                - $data['ad_spend_net']
                - $data['cogs'];

            $margin = $data['gross_sales'] > 0
                ? round($profit / $data['gross_sales'] * 100, 2)
                : 0.0;

            AmazonProfitSnapshot::updateOrCreate(
                [
                    'period_start'        => $settlement->start_date,
                    'period_end'          => $settlement->end_date,
                    'fulfillment_channel' => $data['fulfillment_channel'],
                    'product_type'        => $data['product_type'],
                ],
                array_merge($data, [
                    'period_start'     => $settlement->start_date,
                    'period_end'       => $settlement->end_date,
                    'gross_profit'     => $profit,
                    'gross_margin_pct' => $margin,
                ])
            );
        }
    }

    public function postToXero(AmazonSettlement $settlement): void
    {
        $settlementId = $settlement->settlement_id;
        $endDate      = $settlement->end_date?->toDateString()   ?? now()->toDateString();
        $startDate    = $settlement->start_date?->toDateString() ?? $endDate;

        $statementLines = [];
        $salesCodes     = ['4000', '4001', '4002'];

        // --- 1. One CREDIT/DEBIT per order (Principal + FBM Shipping) ---
        $orderAmounts  = [];
        $orderLabels   = [];
        $orderPostedAt = [];

        foreach ($settlement->lines as $line) {
            if (!in_array($line->account_code, $salesCodes, true)) continue;
            if (!$line->order_id) continue;

            $id = $line->order_id;
            $orderAmounts[$id]  = ($orderAmounts[$id] ?? 0.0) + (float) $line->amount_gross;
            $orderLabels[$id]   = $line->unleashed_order_no ?? $id;
            $orderPostedAt[$id] = $orderPostedAt[$id] ?? ($line->posted_date?->toDateString() ?? $endDate);
        }

        foreach ($orderAmounts as $orderId => $amount) {
            if (round($amount, 2) == 0) continue;
            $statementLines[] = [
                'postedDate'           => $orderPostedAt[$orderId] ?? $endDate,
                'description'          => $orderLabels[$orderId],
                'amount'               => round(abs($amount), 2),
                'creditDebitIndicator' => $amount >= 0 ? 'CREDIT' : 'DEBIT',
                'transactionId'        => 'amz-' . $settlementId . '-order-' . $orderId,
            ];
        }

        // --- 2. Fee lines grouped by description (subscription, storage, FBA, referral, ads) ---
        $feeGroups = [];
        foreach ($settlement->lines as $line) {
            if (in_array($line->account_code, $salesCodes, true)) continue;

            $label = $line->product_type ?? $line->transaction_type ?? 'Amazon Fee';
            $key   = $line->account_code . '|' . $label;

            if (!isset($feeGroups[$key])) {
                $feeGroups[$key] = [
                    'description' => $label,
                    'amount'      => 0.0,
                ];
            }
            $feeGroups[$key]['amount'] += (float) $line->amount_gross;
        }

        foreach ($feeGroups as $key => $fee) {
            if (round($fee['amount'], 2) == 0) continue;
            $statementLines[] = [
                'postedDate'           => $endDate,
                'description'          => $fee['description'],
                'amount'               => round(abs($fee['amount']), 2),
                'creditDebitIndicator' => $fee['amount'] >= 0 ? 'CREDIT' : 'DEBIT',
                'transactionId'        => 'amz-' . $settlementId . '-fee-' . md5($key),
            ];
        }

        if (empty($statementLines)) {
            throw new \RuntimeException('Settlement has no lines to post.');
        }

        $result = $this->xero->postBankFeedStatement([
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'lines'      => $statementLines,
        ]);

        $settlement->update([
            'status'              => 'posted',
            'xero_transaction_id' => $result['id'] ?? null,
        ]);
    }
}
