<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChurchEnvelopeController extends Controller
{
    public function index(): View
    {
        return view('church-envelopes.index');
    }

    public function generate(Request $request): StreamedResponse|Response
    {
        $request->validate([
            'start_date'       => 'required|date',
            'num_weeks'        => 'required|integer|min:1|max:53',
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

        // Build weekly envelopes with sort timestamp
        $dated   = [];
        $undated = [];

        for ($i = 0; $i < $numWeeks; $i++) {
            $date    = $startDate->copy()->addWeeks($i);
            $dated[] = [
                'type'      => 'Weekly',
                'label'     => 'Week ' . ($i + 1),
                'date'      => $date->format('d/m/Y'),
                'sort_date' => $date->timestamp,
            ];
        }

        // Special envelopes — dated ones interleave with weekly, undated go at the end
        foreach ($request->input('specials', []) as $special) {
            if (empty($special['name'])) continue;
            $isDated = !empty($special['dated']) && !empty($special['date']);
            if ($isDated) {
                $d       = Carbon::parse($special['date']);
                $dated[] = [
                    'type'      => 'Special',
                    'label'     => trim($special['name']),
                    'date'      => $d->format('d/m/Y'),
                    'sort_date' => $d->timestamp,
                ];
            } else {
                $undated[] = [
                    'type'  => 'Special',
                    'label' => trim($special['name']),
                    'date'  => '',
                ];
            }
        }

        // Sort dated envelopes by date, then append undated at the end
        usort($dated, fn($a, $b) => $a['sort_date'] <=> $b['sort_date']);

        $template = array_merge(
            array_map(fn($e) => ['type' => $e['type'], 'label' => $e['label'], 'date' => $e['date']], $dated),
            $undated
        );

        $filename = 'church-envelopes-' . $startDate->format('Y') . '.csv';

        // Stream the CSV row-by-row so large box set counts don't hit memory limits
        return response()->streamDownload(function () use ($setNumbers, $template) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['Set Number', 'Box Set Index', 'Envelope Type', 'Label', 'Date']);
            foreach ($setNumbers as $boxIndex => $setNumber) {
                foreach ($template as $envelope) {
                    fputcsv($fp, [
                        $setNumber,
                        $boxIndex + 1,
                        $envelope['type'],
                        $envelope['label'],
                        $envelope['date'],
                    ]);
                }
            }
            fclose($fp);
        }, $filename, ['Content-Type' => 'text/csv']);
}
