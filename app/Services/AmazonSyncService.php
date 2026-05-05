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
            'start_date'     => $first['settlement-start-date'] ?: null,
            'end_date'       => $first['settlement-end-date'] ?: null,
            'deposit_amount' => (float) ($first['deposit-amount'] ?? 0),
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

    public function classifyLine(array $row): ?array
    {
        $txnType    = $row['transaction-type'] ?? '';
        $amountDesc = $row['amount-description'] ?? '';
        $amount     = (float) ($row['amount'] ?? 0);

        // Skip summary/header rows with no amount and no type
        if ($amount === 0.0 && empty($txnType)) {
            return null;
        }

        $fulfillmentId = $row['fulfillment-id'] ?? '';
        $channel = match($fulfillmentId) {
            'AFN'   => 'FBA',
            'MFN'   => 'FBM',
            default => null,
        };

        $accountCode = match(true) {
            $amountDesc === 'Principal' && $channel === 'FBA'                                                    => '4001',
            $amountDesc === 'Principal'                                                                          => '4000',
            in_array($amountDesc, ['FBAPerUnitFulfillmentFee', 'FBAPerOrderFulfillmentFee',
                                   'FBAWeightBasedFee', 'FBATransactionFee'], true)                              => '6100',
            in_array($amountDesc, ['ReferralFeeToAmazon', 'FixedClosingFee',
                                   'VariableClosingFee', 'Commission'], true)                                    => '6101',
            $amountDesc === 'Shipping' && $channel === 'FBM'                                                     => '4002',
            $amountDesc === 'ShippingChargeback'                                                                  => '4002',
            $txnType === 'advertising'                                                                            => '6102',
            default                                                                                               => '6199',
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
        ];
    }

    public function calculateVat(AmazonSettlement $settlement): void
    {
        $feeAccounts = ['6100', '6101', '6102'];

        $settlement->lines()->each(function (AmazonSettlementLine $line) use ($feeAccounts) {
            $gross = (float) $line->amount_gross;

            if (in_array($line->account_code, $feeAccounts, true)) {
                // Amazon charges UK VAT on fees (VAT-inclusive amount in settlement)
                $net = round($gross / 1.20, 4);
                $vat = round($gross - $net, 4);
                $line->update(['amount_net' => $net, 'vat_amount' => $vat, 'vat_rate' => 20.00]);
            } else {
                // Amazon collects and remits UK VAT under Marketplace Facilitator rules.
                // These sales are outside the scope of our VAT return.
                // ** Confirm this treatment with your accountant before filing **
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

            $net = round($spend / 1.20, 4);
            $vat = round($spend - $net, 4);

            $lines[] = [
                'settlement_id'       => $settlement->id,
                'transaction_type'    => 'advertising',
                'order_id'            => null,
                'sku'                 => null,
                'product_type'        => $campaign['campaignName'] ?? null,
                'fulfillment_channel' => null,
                'amount_gross'        => $spend,
                'amount_net'          => $net,
                'vat_amount'          => $vat,
                'vat_rate'            => 20.00,
                'account_code'        => '6102',
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
        $linesByAccount = [];

        foreach ($settlement->lines as $line) {
            $key = $line->account_code . '|' . ($line->fulfillment_channel ?? '');

            if (!isset($linesByAccount[$key])) {
                $linesByAccount[$key] = [
                    'description'  => $line->product_type ?? $line->transaction_type,
                    'amount_net'   => 0.0,
                    'account_code' => $line->account_code,
                    'tax_type'     => $this->resolveTaxType($line->account_code),
                ];
            }

            $linesByAccount[$key]['amount_net'] += (float) $line->amount_net;
        }

        $payload = [
            'settlement_id' => $settlement->settlement_id,
            'date'          => $settlement->end_date->toDateString(),
            'lines'         => array_values($linesByAccount),
        ];

        try {
            $result = $this->xero->postBankTransaction($payload);

            $settlement->update([
                'status'              => 'posted',
                'xero_transaction_id' => $result['BankTransactionID'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('AmazonSyncService: Xero post failed', [
                'settlement_id' => $settlement->settlement_id,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    private function resolveTaxType(string $accountCode): string
    {
        return match(true) {
            in_array($accountCode, ['4000', '4001', '4002'], true) => 'EXEMPTOUTPUT',
            in_array($accountCode, ['6100', '6101', '6102'], true) => 'INPUT2',
            default                                                  => 'NONE',
        };
    }
}
