<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChurchEnvelopeController extends Controller
{
    public function index(): View
    {
        return view('church-envelopes.index');
    }

    public function generate(Request $request): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'start_date'            => 'required|date',
            'num_weeks'             => 'required|integer|min:1|max:53',
            'church'                => 'required|string|max:200',
            'town'                  => 'required|string|max:200',
            'diocese_1'             => 'nullable|string|max:200',
            'diocese_2'             => 'nullable|string|max:200',
            'diocese_3'             => 'nullable|string|max:200',
            'vt'                    => 'nullable|array',
            'set_number_type'       => 'required|in:sequential,custom,none',
            'seq_start'             => 'required_if:set_number_type,sequential|nullable|integer|min:1',
            'seq_count'             => 'required_if:set_number_type,sequential|nullable|integer|min:1|max:9999',
            'custom_numbers'        => 'required_if:set_number_type,custom|nullable|string',
            'none_copies'           => 'required_if:set_number_type,none|nullable|integer|min:1|max:9999',
            'specials'              => 'nullable|array',
            'specials.*.name'       => 'required_with:specials|string|max:100',
            'specials.*.date'       => 'required_with:specials|date',
            'specials.*.show_date'  => 'nullable',
            'specials.*.vt7'        => 'nullable|string|max:200',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $numWeeks  = (int) $request->num_weeks;
        $church    = trim($request->church);
        $town      = trim($request->town);
        $diocese1  = trim($request->diocese_1 ?? '');
        $diocese2  = trim($request->diocese_2 ?? '');
        $diocese3  = trim($request->diocese_3 ?? '');
        $vtInputs  = $request->input('vt', []);
        $weeklyVt  = [];
        for ($i = 1; $i <= 8; $i++) {
            $weeklyVt[] = trim($vtInputs[$i] ?? '');
        }

        // Resolve set numbers
        if ($request->set_number_type === 'sequential') {
            $start      = (int) $request->seq_start;
            $count      = (int) $request->seq_count;
            $setNumbers = range($start, $start + $count - 1);
        } elseif ($request->set_number_type === 'custom') {
            $setNumbers = [];
            foreach (preg_split('/[\s,;]+/', trim($request->custom_numbers ?? '')) as $part) {
                $part = trim($part);
                if (preg_match('/^(\d+)-(\d+)$/', $part, $m)) {
                    for ($n = (int)$m[1]; $n <= (int)$m[2]; $n++) {
                        $setNumbers[] = $n;
                    }
                } elseif (ctype_digit($part) && $part !== '') {
                    $setNumbers[] = (int)$part;
                }
            }
            $setNumbers = array_values(array_unique($setNumbers));
        } else {
            // No set numbers — generate N anonymous slots (null = blank in output)
            $copies     = max(1, (int) $request->none_copies);
            $setNumbers = array_fill(0, $copies, null);
        }

        if (empty($setNumbers)) {
            return back()->withErrors(['custom_numbers' => 'No valid set numbers found.']);
        }

        // Build envelope template — weekly envelopes sorted by date, specials interleaved by date
        $envelopes = [];

        for ($i = 0; $i < $numWeeks; $i++) {
            $date        = $startDate->copy()->addWeeks($i);
            $envelopes[] = [
                'carbon'     => $date,
                'sort_date'  => $date->timestamp,
                'is_special' => false,
            ];
        }

        foreach ($request->input('specials', []) as $special) {
            if (empty($special['name']) || empty($special['date'])) continue;
            $d           = Carbon::parse($special['date']);
            $envelopes[] = [
                'carbon'       => $d,
                'sort_date'    => $d->timestamp,
                'is_special'   => true,
                'special_name' => trim($special['name']),
                'show_date'    => !empty($special['show_date']),
                'special_vt7'  => trim($special['vt7'] ?? ''),
            ];
        }

        // Sort everything by date so specials interleave with weekly
        usort($envelopes, fn($a, $b) => $a['sort_date'] <=> $b['sort_date']);

        // Build spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->fromArray(
            ['0', 'DAY', 'MONTH', 'YEAR', '-1', '-2', '@imag', '@imag',
             'CHURCH', 'TOWN', 'DIOCESE LINE 1', 'DIOCESE LINE 2', 'DIOCESE LINE 3',
             'VT1', 'VT2', 'VT3', 'VT4', 'VT5', 'VT6', 'VT7', 'VT8'],
            null, 'A1'
        );

        $row     = 2;
        $lineNum = 1;

        // Pair set numbers (1,2), (3,4)… each row = one physical 2-up sheet
        for ($i = 0; $i < count($setNumbers); $i += 2) {
            $setLeft  = $setNumbers[$i];           // null → blank
            $setRight = $setNumbers[$i + 1] ?? null; // null → blank

            foreach ($envelopes as $envelope) {
                $carbon    = $envelope['carbon'];
                $isSpecial = $envelope['is_special'];

                // Date columns: blank for specials where show_date is off
                if ($isSpecial && !$envelope['show_date']) {
                    $day = $month = $year = '';
                } else {
                    $day   = (int) $carbon->format('j');
                    $month = strtoupper($carbon->format('M'));
                    $year  = (int) $carbon->format('Y');
                }

                // VT columns: specials use VT6=title, VT7=offering text; weekly use standard VT
                $rowVt = $isSpecial
                    ? ['', '', '', '', '', $envelope['special_name'], $envelope['special_vt7'], '']
                    : $weeklyVt;

                $sheet->fromArray(
                    [$lineNum, $day, $month, $year,
                     $setLeft ?? '', $setRight ?? '',
                     '', '',
                     $church, $town, $diocese1, $diocese2, $diocese3,
                     ...$rowVt],
                    null, 'A' . $row
                );
                $row++;
                $lineNum++;
            }
        }

        set_time_limit(0);
        $tmpFile = tempnam(sys_get_temp_dir(), 'envelopes_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmpFile);

        return response()->download(
            $tmpFile,
            'church-envelopes-' . $startDate->format('Y') . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }
}
