<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\StockWatchlistSubstitution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProcessSalesImport extends Command
{
    protected $signature   = 'imports:process-sales {file : Path to the saved import file}';
    protected $description = 'Process a saved sales import file into the sales_lines table';

    public function handle(): void
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return;
        }

        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $rows = in_array($ext, ['xlsx', 'xls'])
            ? $this->parseSpreadsheet($file)
            : $this->parseCsv($file);

        @unlink($file);

        if (empty($rows)) {
            $this->error('File appears empty.');
            return;
        }

        $header = array_map(fn($h) => strtolower(trim((string)($h ?? ''))), $rows[0]);

        $colOrderNo       = array_search('order no.',     $header);
        $colOrderDate     = array_search('order date',    $header);
        $colRequiredDate  = array_search('required date', $header);
        $colCompletedDate = array_search('completed date',$header);
        $colWarehouse     = array_search('warehouse',     $header);
        $colCustomer      = array_search('customer code', $header);
        $colCustomerName  = array_search('customer',      $header);
        $colCustomerType  = array_search('customer type', $header);
        $colProduct       = array_search('product code',  $header);
        $colProductGroup  = array_search('product group', $header);
        $colStatus        = array_search('status',        $header);
        $colQty           = array_search('quantity',      $header);
        $colSubTotal      = array_search('sub total',     $header);

        $substitutions = StockWatchlistSubstitution::all()->map(fn($s) => [
            'find'    => strtoupper($s->find),
            'replace' => strtoupper($s->replace),
        ])->all();

        array_shift($rows);

        $insertRows = [];
        $now        = now()->toDateTimeString();

        foreach ($rows as $row) {
            $status = strtolower(trim((string)($colStatus !== false ? ($row[$colStatus] ?? '') : '')));
            if ($status === 'cancelled') continue;

            $orderDate = $this->parseDate($row[$colOrderDate] ?? null, $ext);
            if ($orderDate === null) continue;

            $productCode = strtoupper(substr(trim((string)($colProduct !== false ? ($row[$colProduct] ?? '') : '')), 0, 100));
            foreach ($substitutions as $sub) {
                if ($productCode && str_contains($productCode, $sub['find'])) {
                    $productCode = str_replace($sub['find'], $sub['replace'], $productCode);
                }
            }

            $insertRows[] = [
                'order_no'        => $colOrderNo !== false      ? (substr(trim((string)($row[$colOrderNo] ?? '')), 0, 50) ?: null) : null,
                'order_date'      => $orderDate->format('Y-m-d'),
                'required_date'   => $colRequiredDate  !== false ? ($this->parseDate($row[$colRequiredDate]  ?? null, $ext)?->format('Y-m-d')) : null,
                'completed_date'  => $colCompletedDate !== false ? ($this->parseDate($row[$colCompletedDate] ?? null, $ext)?->format('Y-m-d')) : null,
                'warehouse'       => $colWarehouse     !== false ? (substr(trim((string)($row[$colWarehouse] ?? '')), 0, 100) ?: null) : null,
                'customer_code'   => $colCustomer      !== false ? (substr(trim((string)($row[$colCustomer] ?? '')), 0, 100) ?: null) : null,
                'customer'        => $colCustomerName  !== false ? (substr(trim((string)($row[$colCustomerName] ?? '')), 0, 255) ?: null) : null,
                'customer_type'   => $colCustomerType  !== false ? (substr(trim((string)($row[$colCustomerType] ?? '')), 0, 100) ?: null) : null,
                'product_code'    => $productCode ?: null,
                'product_group'   => $colProductGroup  !== false ? (substr(trim((string)($row[$colProductGroup] ?? '')), 0, 100) ?: null) : null,
                'status'          => $status ? substr($status, 0, 50) : null,
                'quantity'        => max(0, (float)str_replace([',', '£', '$', '€'], '', $colQty !== false ? ($row[$colQty] ?? 0) : 0)),
                'sub_total'       => max(0, (float)str_replace([',', '£', '$', '€'], '', $colSubTotal !== false ? ($row[$colSubTotal] ?? 0) : 0)),
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        $count = count($insertRows);
        $this->info("Building complete — {$count} rows to insert.");

        try {
            DB::statement('TRUNCATE TABLE sales_lines');
            DB::transaction(function () use ($insertRows) {
                foreach (array_chunk($insertRows, 4000) as $chunk) {
                    DB::table('sales_lines')->insert($chunk);
                }
            });
        } catch (\Throwable $e) {
            ActivityLog::record('imports.sales.error', 'Import failed: ' . $e->getMessage());
            $this->error('Import failed: ' . $e->getMessage());
            return;
        }

        ActivityLog::record('imports.sales', "Imported {$count} sales line(s)");
        $this->info("Done. Imported {$count} sales line(s).");
    }

    private function parseSpreadsheet(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        return $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);
    }

    private function parseCsv(string $path): array
    {
        $content = file_get_contents($path);
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        } elseif (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $content   = str_replace("\r\n", "\n", str_replace("\r", "\n", $content));
        $lines     = explode("\n", trim($content));
        $delimiter = str_contains($lines[0] ?? '', "\t") ? "\t" : ',';
        return array_map(fn($line) => str_getcsv($line, $delimiter), $lines);
    }

    private function parseDate(mixed $raw, string $ext): ?\DateTime
    {
        if ($raw === null || $raw === '') return null;

        if (in_array($ext, ['xlsx', 'xls']) && is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject($raw);
            } catch (\Throwable) {
                return null;
            }
        }

        return \DateTime::createFromFormat('d/m/Y', (string)$raw)
            ?: \DateTime::createFromFormat('Y-m-d', (string)$raw)
            ?: (($ts = strtotime((string)$raw)) ? (new \DateTime())->setTimestamp($ts) : null);
    }
}
