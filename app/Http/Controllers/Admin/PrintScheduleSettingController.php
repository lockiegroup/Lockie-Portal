<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrintScheduleSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintScheduleSettingController extends Controller
{
    private const DAYS = [
        1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu',
        5 => 'Fri', 6 => 'Sat', 0 => 'Sun',
    ];

    public function index(): View
    {
        $workingDays = json_decode(PrintScheduleSetting::getValue('working_days', '[1,2,3,4]'), true) ?? [1, 2, 3, 4];

        $settings = [
            'working_days'      => $workingDays,
            'work_start'        => PrintScheduleSetting::getValue('work_start', '08:00'),
            'work_end'          => PrintScheduleSetting::getValue('work_end', '16:30'),
            'break_minutes'     => PrintScheduleSetting::getValue('break_minutes', '30'),
            'throughput_auto_1' => PrintScheduleSetting::getValue('throughput_auto_1', '350'),
            'throughput_auto_2' => PrintScheduleSetting::getValue('throughput_auto_2', '350'),
            'throughput_auto_3' => PrintScheduleSetting::getValue('throughput_auto_3', '350'),
            'throughput_baby'   => PrintScheduleSetting::getValue('throughput_baby', '180'),
        ];

        $days = self::DAYS;

        return view('admin.print-schedule-settings', compact('settings', 'days'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'working_days'      => 'nullable|array',
            'working_days.*'    => 'integer|in:0,1,2,3,4,5,6',
            'work_start'        => 'required|date_format:H:i',
            'work_end'          => 'required|date_format:H:i',
            'break_minutes'     => 'required|integer|min:0|max:480',
            'throughput_auto_1' => 'required|integer|min:1',
            'throughput_auto_2' => 'required|integer|min:1',
            'throughput_auto_3' => 'required|integer|min:1',
            'throughput_baby'   => 'required|integer|min:1',
        ]);

        $days = array_map('intval', $request->input('working_days', []));
        PrintScheduleSetting::setValue('working_days',      json_encode($days));
        PrintScheduleSetting::setValue('work_start',        $request->input('work_start'));
        PrintScheduleSetting::setValue('work_end',          $request->input('work_end'));
        PrintScheduleSetting::setValue('break_minutes',     (string) $request->integer('break_minutes'));
        PrintScheduleSetting::setValue('throughput_auto_1', (string) $request->integer('throughput_auto_1'));
        PrintScheduleSetting::setValue('throughput_auto_2', (string) $request->integer('throughput_auto_2'));
        PrintScheduleSetting::setValue('throughput_auto_3', (string) $request->integer('throughput_auto_3'));
        PrintScheduleSetting::setValue('throughput_baby',   (string) $request->integer('throughput_baby'));

        return back()->with('success', 'Settings saved.');
    }
}
