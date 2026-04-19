<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ChurchEnvelopeController extends Controller
{
    public function index(): View
    {
        return view('church-envelopes.index');
    }

    public function generate(Request $request): Response
    {
        $request->validate([
            'start_date'       => 'required|date',
            'num_weeks'        => 'required|integer|min:1|max:53',
            'set_number_type'  => 'required|in:sequential,custom',
            'seq_start'        => 'required_if:set_number_type,sequential|nullable|integer|min:1',
            'seq_count'        => 'required_if:set_number_type,sequential|nullable|integer|min:1',
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

        // Build envelope template (same for every set)
        $template = [];

        for ($i = 0; $i < $numWeeks; $i++) {
            $template[] = [
                'type'  => 'Weekly',
                'label' => 'Week ' . ($i + 1),
                'date'  => $startDate->copy()->addWeeks($i)->format('d/m/Y'),
            ];
        }

        foreach ($request->input('specials', []) as $special) {
            if (empty($special['name'])) continue;
            $dated      = !empty($special['dated']);
            $date       = ($dated && !empty($special['date']))
                ? Carbon::parse($special['date'])->format('d/m/Y')
                : '';
            $template[] = [
                'type'  => 'Special',
                'label' => trim($special['name']),
                'date'  => $date,
            ];
        }

        // Build CSV — one row per physical envelope
        $rows   = [];
        $rows[] = ['Set Number', 'Box Set Index', 'Envelope Type', 'Label', 'Date'];

        foreach ($setNumbers as $boxIndex => $setNumber) {
            foreach ($template as $envelope) {
                $rows[] = [
                    $setNumber,
                    $boxIndex + 1,
                    $envelope['type'],
                    $envelope['label'],
                    $envelope['date'],
                ];
            }
        }

        $csv      = $this->toCsv($rows);
        $filename = 'church-envelopes-' . $startDate->format('Y') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function toCsv(array $rows): string
    {
        $fp = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }
}
