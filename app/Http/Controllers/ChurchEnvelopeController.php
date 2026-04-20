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
            'start_date'       => 'required|date',
            'num_weeks'        => 'required|integer|min:1|max:53',
            'church'           => 'required|string|max:200',
            'town'             => 'required|string|max:200',
            'diocese_1'        => 'nullable|string|max:200',
            'diocese_2'        => 'nullable|string|max:200',
            'diocese_3'        => 'nullable|string|max:200',
            'vt'               => 'nullable|array',
            'set_number_type'  => 'required|in:sequential,custom',
            'seq_start'        => 'required_if:set_number_type,sequential|nullable|integer|min:1',
            'seq_count'        => 'required_if:set_number_type,sequential|nullable|integer|min:1|max:9999',
            'custom_numbers'   => 'required_if:set_number_type,custom|nullable|string',
            'specials'         => 'nullable|array',
            'specials.*.name'  => 'required_with:specials|string|max:100',
            'specials.*.dated' => 'nullable',
            'specials.*.date'  => 'nullable|date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $numWeeks  = (int) $request->num_weeks;
        $church    = trim($request->church);
        $town      = trim($request->town);
        $diocese1  = trim($request->diocese_1 ?? '');
        $diocese2  = trim($request->diocese_2 ?? '');
        $diocese3  = trim($request->diocese_3 ?? '');
        $vtInputs  = $request->input('vt', []);
        $vt        = [];
        for ($i = 1; $i <= 8; $i++) {
            $vt[] = trim($vtInputs[$i] ?? '');
        }

        // Resolve set numbers
        if ($request->set_number_type === 'sequential') {
            $start      = (int) $request->seq_start;
            $count      = (int) $request->seq_count;
            $setNumbers = range($start, $start + $count - 1);
        } else {
            $raw        = preg_split('/[\s,;]+/', trim($request->custom_numbers ?? ''));
            $setNumbers = array_values(array_filter(array_map('intval', array_filter($raw))));
        }

        if (empty($setNumbers)) {
            return back()->withErrors(['custom_numbers' => 'No valid set numbers found.']);
        }

        // Build envelope template (dated first, sorted; undated appended)
        $dated   = [];
        $undated = [];

        for ($i = 0; $i < $numWeeks; $i++) {
            $date    = $startDate->copy()->addWeeks($i);
            $dated[] = ['carbon' => $date, 'sort_date' => $date->timestamp];
        }

        foreach ($request->input('specials', []) as $special) {
            if (empty($special['name'])) continue;
            $isDated = !empty($special['dated']) && !empty($special['date']);
            if ($isDated) {
                $d       = Carbon::parse($special['date']);
                $dated[] = ['carbon' => $d, 'sort_date' => $d->timestamp];
            } else {
                $undated[] = ['carbon' => null];
            }
        }

        usort($dated, fn($a, $b) => $a['sort_date'] <=> $b['sort_date']);
        $template = array_merge($dated, $undated);

        // Build spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->fromArray(
            ['0', 'DAY', 'MONTH', 'YEAR', '-1', '-2', '@imag', '@imag',
             'CHURCH', 'TOWN', 'DIOCESE LINE 1', 'DIOCESE LINE 2', 'DIOCESE LINE 3',
             'VT1', 'VT2', 'VT3', 'VT4', 'VT5', 'VT6', 'VT7', 'VT8'],
            null, 'A1'
        );

        $row = 2;
        foreach ($setNumbers as $setNumber) {
            foreach ($template as $envelope) {
                $carbon = $envelope['carbon'];
                $day    = $carbon ? (int) $carbon->format('j') : '';
                $month  = $carbon ? strtoupper($carbon->format('M')) : '';
                $year   = $carbon ? (int) $carbon->format('Y') : '';

                $sheet->fromArray(
                    [$setNumber, $day, $month, $year, $setNumber, $setNumber, '', '',
                     $church, $town, $diocese1, $diocese2, $diocese3,
                     ...$vt],
                    null, 'A' . $row
                );
                $row++;
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
