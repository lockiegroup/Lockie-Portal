<?php

namespace App\Http\Controllers;

use App\Models\EnvelopeDesign;
use App\Models\EnvelopeSetting;
use App\Models\EnvelopeVerse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChurchEnvelopeController extends Controller
{
    public function index(): View
    {
        $verses  = EnvelopeVerse::orderBy('sort_order')->get();
        $designs = EnvelopeDesign::orderBy('sort_order')->orderBy('name')->get();

        return view('church-envelopes.index', compact('verses', 'designs'));
    }

    public function generate(Request $request): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'start_date'           => 'required|date',
            'num_weeks'            => 'required|integer|min:1|max:53',
            'church'               => 'required|string|max:200',
            'town'                 => 'required|string|max:200',
            'diocese_1'            => 'nullable|string|max:200',
            'diocese_2'            => 'nullable|string|max:200',
            'diocese_3'            => 'nullable|string|max:200',
            'vt'                   => 'nullable|array',
            'set_numbers'          => 'nullable|string',
            'none_copies'          => 'nullable|integer|min:0',
            'design_id'            => 'nullable|integer|exists:envelope_designs,id',
            'specials'             => 'nullable|array',
            'specials.*.name'      => 'nullable|string|max:100',
            'specials.*.date'      => 'nullable|date',
            'specials.*.show_date' => 'nullable',
            'specials.*.position'  => 'nullable|in:before,after,back',
            'specials.*.vt7'       => 'nullable|string|max:200',
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

        $imagePath   = $request->design_id
            ? EnvelopeDesign::find($request->design_id)?->path ?? ''
            : '';
        $spiralPath  = EnvelopeSetting::getValue('spiral_image_path');

        $verses = EnvelopeVerse::orderBy('sort_order')->get();

        // Resolve set numbers — supports ranges (1-50), individual numbers, or blank
        $setNumbers = [];
        foreach (preg_split('/[\s,;]+/', trim($request->set_numbers ?? '')) as $part) {
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

        // Append unnumbered copies (null = blank set number columns)
        $noneCopies = max(0, (int) ($request->none_copies ?? 0));
        for ($i = 0; $i < $noneCopies; $i++) {
            $setNumbers[] = null;
        }

        if (empty($setNumbers)) {
            return back()->withErrors(['set_numbers' => 'Enter set numbers and/or at least 1 unnumbered copy.']);
        }

        // Build weekly envelopes
        $weekly = [];
        for ($i = 0; $i < $numWeeks; $i++) {
            $date     = $startDate->copy()->addWeeks($i);
            $weekly[] = [
                'carbon'     => $date,
                'sort_date'  => $date->timestamp,
                'priority'   => 1,   // weekly sits between before/after specials on same date
                'is_special' => false,
            ];
        }

        // Build special envelopes — split by position
        $mainSpecials = [];  // before/after: interleave with weekly by date
        $backSpecials = [];  // back: appended after all weekly, ordered by their date

        foreach ($request->input('specials', []) as $special) {
            if (empty($special['name']) || empty($special['date'])) continue;
            $d        = Carbon::parse($special['date']);
            $position = $special['position'] ?? 'before';
            $entry    = [
                'carbon'       => $d,
                'sort_date'    => $d->timestamp,
                'priority'     => $position === 'after' ? 2 : 0,
                'is_special'   => true,
                'special_name' => trim($special['name']),
                'show_date'    => !empty($special['show_date']),
                'special_vt7'  => trim($special['vt7'] ?? ''),
            ];
            if ($position === 'back') {
                $backSpecials[] = $entry;
            } else {
                $mainSpecials[] = $entry;
            }
        }

        // Main block: weekly + before/after specials sorted by (date, priority)
        $main = array_merge($weekly, $mainSpecials);
        usort($main, function ($a, $b) {
            return $a['sort_date'] !== $b['sort_date']
                ? $a['sort_date'] <=> $b['sort_date']
                : $a['priority'] <=> $b['priority'];
        });

        // Back specials sorted by their own date, appended last
        usort($backSpecials, fn($a, $b) => $a['sort_date'] <=> $b['sort_date']);

        $template = array_reverse(array_merge($main, $backSpecials));

        // Build spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->fromArray(
            ['0', 'DAY', 'MONTH', 'YEAR', '-1', '-2', "'@image", "'@image",
             'CHURCH', 'TOWN', 'DIOCESE LINE 1', 'DIOCESE LINE 2', 'DIOCESE LINE 3',
             'VT1', 'VT2', 'VT3', 'VT4', 'VT5', 'VT6', 'VT7', 'VT8'],
            null, 'A1'
        );

        $row     = 2;
        $lineNum = 1;

        // Pair set numbers: (1,2), (3,4)… each row = one physical 2-up sheet
        for ($i = 0; $i < count($setNumbers); $i += 2) {
            $setLeft  = $setNumbers[$i];
            $setRight = $setNumbers[$i + 1] ?? null;

            foreach ($template as $envelope) {
                $carbon    = $envelope['carbon'];
                $isSpecial = $envelope['is_special'];

                if ($isSpecial && !$envelope['show_date']) {
                    $day = $month = $year = '';
                } else {
                    $day   = (int) $carbon->format('j');
                    $month = strtoupper($carbon->format('M'));
                    $year  = (int) $carbon->format('Y');
                }

                $rowVt = $isSpecial
                    ? ['', '', '', '', '', $envelope['special_name'], $envelope['special_vt7'], '']
                    : $weeklyVt;

                $sheet->fromArray(
                    [$lineNum, $day, $month, $year,
                     $setLeft ?? '', $setRight ?? '',
                     $isSpecial ? '' : $imagePath, $isSpecial ? $spiralPath : '',
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

    public function parse(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $allRows     = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            array_shift($allRows); // remove header row

            $church = $town = $diocese1 = $diocese2 = $diocese3 = $imagePath = '';
            $weeklyVts    = array_fill(0, 8, '');
            $weeklyDates  = [];
            $setNums      = [];
            $seenSpecials = [];
            $specials     = [];
            $parsedStatic = false;
            $blankSetRows = 0;

            foreach ($allRows as $row) {
                if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                // Columns: 0=lineNum,1=DAY,2=MONTH,3=YEAR,4=setL,5=setR,
                //          6=G(design),7=H(spiral),8=church,9=town,
                //          10=diocese1,11=diocese2,12=diocese3, 13-20=VT1-VT8
                $vt1    = (string) ($row[13] ?? '');
                $vt6    = (string) ($row[18] ?? '');
                $spiral = (string) ($row[7]  ?? '');

                // Specials have VT6 set and VT1 blank (or a spiral path in col H)
                $isSpecial = $spiral !== '' || ($vt6 !== '' && $vt1 === '');

                if (!$parsedStatic) {
                    $church   = (string) ($row[8]  ?? '');
                    $town     = (string) ($row[9]  ?? '');
                    $diocese1 = (string) ($row[10] ?? '');
                    $diocese2 = (string) ($row[11] ?? '');
                    $diocese3 = (string) ($row[12] ?? '');
                    $parsedStatic = true;
                }

                // Collect numbered set values from columns E and F
                $setLeft  = $row[4] ?? '';
                $setRight = $row[5] ?? '';
                foreach ([$setLeft, $setRight] as $v) {
                    if (is_numeric($v) && (int) $v > 0) {
                        $setNums[(int) $v] = true;
                    }
                }

                // Count rows belonging to unnumbered (blank) sets
                if (($setLeft === '' || $setLeft === null) && ($setRight === '' || $setRight === null)) {
                    $blankSetRows++;
                }

                if (!$isSpecial) {
                    // First weekly row gives us VT1-VT8 and design path
                    if ($imagePath === '' && ($row[6] ?? '') !== '') {
                        $imagePath = (string) $row[6];
                    }
                    if (empty(array_filter($weeklyVts))) {
                        for ($i = 0; $i < 8; $i++) {
                            $weeklyVts[$i] = (string) ($row[13 + $i] ?? '');
                        }
                    }

                    $day   = $row[1] ?? '';
                    $month = ucfirst(strtolower((string) ($row[2] ?? '')));
                    $year  = $row[3] ?? '';
                    if ($day !== '' && $month !== '' && $year !== '') {
                        try {
                            $d = Carbon::createFromFormat('j M Y', "{$day} {$month} {$year}");
                            $weeklyDates[$d->format('Y-m-d')] = true;
                        } catch (\Exception) {}
                    }
                } elseif ($vt6 !== '' && !isset($seenSpecials[$vt6])) {
                    $seenSpecials[$vt6] = true;
                    $day   = $row[1] ?? '';
                    $month = ucfirst(strtolower((string) ($row[2] ?? '')));
                    $year  = $row[3] ?? '';
                    $specialDate = '';
                    if ($day !== '' && $month !== '' && $year !== '') {
                        try {
                            $specialDate = Carbon::createFromFormat('j M Y', "{$day} {$month} {$year}")->format('Y-m-d');
                        } catch (\Exception) {}
                    }
                    $specials[] = [
                        'name'      => $vt6,
                        'date'      => $specialDate,
                        'vt7'       => (string) ($row[19] ?? ''),
                        'show_date' => $day !== '' || $month !== '',
                        'position'  => 'after',
                    ];
                }
            }

            ksort($weeklyDates);
            $startDate    = array_key_first($weeklyDates) ?? '';
            $numWeeks     = count($weeklyDates);
            $templateSize = $numWeeks + count($seenSpecials);

            // Each unnumbered "block" in the spreadsheet represents a pair of copies.
            // Divide blank rows by template size to get block count, then × 2 for copies.
            $noneCopies = $templateSize > 0
                ? intdiv($blankSetRows, $templateSize) * 2
                : 0;

            $setNumsList = array_keys($setNums);
            sort($setNumsList);

            $designId = $imagePath !== ''
                ? EnvelopeDesign::where('path', $imagePath)->value('id')
                : null;

            return response()->json([
                'success'      => true,
                'church'       => $church,
                'town'         => $town,
                'diocese_1'    => $diocese1,
                'diocese_2'    => $diocese2,
                'diocese_3'    => $diocese3,
                'vts'          => $weeklyVts,
                'start_date'   => $startDate,
                'num_weeks'    => $numWeeks,
                'set_numbers'  => $this->buildRangeString($setNumsList),
                'none_copies'  => $noneCopies,
                'design_id'    => $designId,
                'specials'     => $specials,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    private function buildRangeString(array $nums): string
    {
        if (empty($nums)) return '';
        $ranges = [];
        $start  = $end = $nums[0];
        for ($i = 1; $i < count($nums); $i++) {
            if ($nums[$i] === $end + 1) {
                $end = $nums[$i];
            } else {
                $ranges[] = $start === $end ? (string) $start : "{$start}-{$end}";
                $start    = $end = $nums[$i];
            }
        }
        $ranges[] = $start === $end ? (string) $start : "{$start}-{$end}";
        return implode(', ', $ranges);
    }
}
