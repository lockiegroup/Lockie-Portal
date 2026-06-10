<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;

class LetterFilterController extends Controller
{
    public function index()
    {
        return view('letter-filter.index');
    }

    public function process(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'pdf'   => ['required', 'file', 'mimes:pdf', 'max:102400'], // 100 MB
            'codes' => ['required', 'string'],
        ]);

        try {

        // Parse the pasted account codes — split on whitespace, commas, newlines
        $rawCodes    = preg_split('/[\s,;]+/', strtoupper(trim($request->input('codes'))), -1, PREG_SPLIT_NO_EMPTY);
        $orderedSet  = array_flip(array_filter($rawCodes));

        $pdfPath = $request->file('pdf')->getRealPath();

        // Extract text per page
        $parser   = new Parser();
        $pdf      = $parser->parseFile($pdfPath);
        $pages    = $pdf->getPages();
        $total    = count($pages);

        $results  = [];   // ['page' => n, 'code' => 'X0000', 'excluded' => bool]
        $noCode   = [];   // page numbers with no code found
        $seen     = [];   // code => first page (for duplicate detection)

        foreach ($pages as $i => $page) {
            $pageNum = $i + 1;
            $text    = $page->getText();
            $code    = $this->extractCode($text);

            if (!$code) {
                $noCode[] = $pageNum;
                $results[] = ['page' => $pageNum, 'code' => null, 'excluded' => false];
                continue;
            }

            $duplicate = isset($seen[$code]);
            $seen[$code] = $seen[$code] ?? $pageNum;
            $excluded  = isset($orderedSet[$code]);

            $results[] = [
                'page'      => $pageNum,
                'code'      => $code,
                'excluded'  => $excluded,
                'duplicate' => $duplicate,
            ];
        }

        $excluded  = array_filter($results, fn($r) => $r['excluded']);
        $included  = array_filter($results, fn($r) => !$r['excluded'] && $r['code'] !== null);
        $noCodePages = $noCode;

        $includedPages = array_map(fn($r) => $r['page'], array_values($included));
        $noCodeIncluded = array_filter($results, fn($r) => !$r['excluded'] && $r['code'] === null);
        foreach ($noCodeIncluded as $r) {
            $includedPages[] = $r['page'];
        }
        sort($includedPages);

        // Build filtered PDF
        $filteredPdf = $this->buildFilteredPdf($pdfPath, $includedPages);

        // Build CSVs
        $excludedCsv = $this->buildCsv(
            ['Page', 'Account Code'],
            array_map(fn($r) => [$r['page'], $r['code']], array_values($excluded))
        );
        $includedCsv = $this->buildCsv(
            ['Page', 'Account Code'],
            array_map(fn($r) => [$r['page'], $r['code'] ?? '(no code)'], array_values($included))
        );
        $exceptionCsv = $this->buildCsv(
            ['Page', 'Issue', 'Account Code'],
            array_merge(
                array_map(fn($p) => [$p, 'No account code found', ''], $noCodePages),
                array_map(fn($r) => [$r['page'], 'Duplicate account code', $r['code']],
                    array_values(array_filter($results, fn($r) => !empty($r['duplicate'])))
                )
            )
        );

        // Store everything in session-keyed temp files
        $key = bin2hex(random_bytes(12));
        $dir = storage_path("app/letter-filter/{$key}");
        mkdir($dir, 0755, true);
        file_put_contents("{$dir}/filtered.pdf",      $filteredPdf);
        file_put_contents("{$dir}/excluded.csv",      $excludedCsv);
        file_put_contents("{$dir}/included.csv",      $includedCsv);
        file_put_contents("{$dir}/exceptions.csv",    $exceptionCsv);
        file_put_contents("{$dir}/meta.json", json_encode([
            'total'     => $total,
            'excluded'  => count($excluded),
            'included'  => count($includedPages),
            'no_code'   => count($noCodePages),
            'duplicates'=> count(array_filter($results, fn($r) => !empty($r['duplicate']))),
        ]));

        // Clean up old temp dirs (older than 2 hours)
        $this->cleanOldDirs(storage_path('app/letter-filter'));

        return response()->json(['key' => $key]);

        } catch (\Throwable $e) {
            \Log::error('Letter filter failed', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['error' => 'Processing failed: ' . $e->getMessage()], 500);
        }
    }

    public function download(Request $request, string $key, string $file)
    {
        $allowed = ['filtered.pdf', 'excluded.csv', 'included.csv', 'exceptions.csv', 'meta.json'];
        abort_unless(in_array($file, $allowed) && preg_match('/^[a-z0-9_.]+$/i', $key), 404);

        $path = storage_path("app/letter-filter/{$key}/{$file}");
        abort_unless(file_exists($path), 404);

        $mime = str_ends_with($file, '.pdf') ? 'application/pdf' : 'text/csv';
        return response()->download($path, $file, ['Content-Type' => $mime]);
    }

    private function extractCode(string $text): ?string
    {
        // Primary: "ACC NO: X0000" when parser keeps label and value together
        if (preg_match('/ACC\s*NO[:\s]+([A-Z]{1,3}\d{3,6})/i', $text, $m)) {
            return strtoupper(trim($m[1]));
        }
        // Fallback: account code appears as a standalone line (e.g. smalot splits fields)
        // Pattern: exactly one letter followed by 4–6 digits, alone on its own line
        if (preg_match('/^([A-Z]\d{4,6})$/m', $text, $m)) {
            return strtoupper(trim($m[1]));
        }
        return null;
    }

    private function buildFilteredPdf(string $sourcePath, array $pages): string
    {
        if (empty($pages)) {
            // Return a minimal valid PDF with a blank page
            $fpdi = new Fpdi();
            $fpdi->AddPage();
            return $fpdi->Output('', 'S');
        }

        $fpdi = new Fpdi();
        $fpdi->setSourceFile($sourcePath);

        foreach ($pages as $pageNum) {
            $tpl = $fpdi->importPage($pageNum);
            $size = $fpdi->getTemplateSize($tpl);
            $fpdi->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
            $fpdi->useTemplate($tpl);
        }

        return $fpdi->Output('', 'S');
    }

    private function buildCsv(array $headers, array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    private function cleanOldDirs(string $base): void
    {
        if (!is_dir($base)) return;
        foreach (scandir($base) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = "{$base}/{$entry}";
            if (is_dir($path) && filemtime($path) < time() - 7200) {
                array_map('unlink', glob("{$path}/*"));
                rmdir($path);
            }
        }
    }
}
