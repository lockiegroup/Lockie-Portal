<?php

namespace App\Console\Commands;

use App\Models\KeyAccount;
use App\Services\UnleashedService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FetchKeyAccountSales extends Command
{
    protected $signature   = 'key-accounts:fetch-sales {--year= : Specific year to fetch (defaults to current and previous year)}';
    protected $description = 'Pre-fetch and cache Key Account quarterly sales figures from Unleashed';

    public function handle(): int
    {
        $customerCodes = KeyAccount::pluck('account_code')->all();

        if (empty($customerCodes)) {
            $this->warn('No key accounts configured — nothing to fetch.');
            return 0;
        }

        $years = $this->option('year')
            ? [(int) $this->option('year')]
            : [now()->year - 1, now()->year];

        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key')
        );

        foreach ($years as $year) {
            $this->info("Fetching {$year} sales...");

            // Clear stale cache so we always get fresh data
            Cache::forget("unleashed_ka_sales_year_{$year}");

            $data = $unleashed->fetchSalesByCustomerCodes($customerCodes, $year);

            $withSales = array_filter($data, fn($d) => $d['total'] > 0);
            $grandTotal = array_sum(array_column($data, 'total'));

            $this->info("  {$year}: " . count($withSales) . ' account(s) with sales, total £' . number_format($grandTotal, 2));

            foreach ($withSales as $code => $sales) {
                $this->line(sprintf(
                    '    %-20s £%s  (Q1 £%s  Q2 £%s  Q3 £%s  Q4 £%s)',
                    $code,
                    number_format($sales['total'], 2),
                    number_format($sales['q1'], 2),
                    number_format($sales['q2'], 2),
                    number_format($sales['q3'], 2),
                    number_format($sales['q4'], 2),
                ));
            }

            if (empty($withSales)) {
                $this->warn('  No sales found. Check that account codes match Unleashed CustomerCodes exactly.');
            }
        }

        $this->info('Done.');
        return 0;
    }
}
