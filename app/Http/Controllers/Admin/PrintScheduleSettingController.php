<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrintScheduleSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintScheduleSettingController extends Controller
{
    public function index(): View
    {
        $settings = [
            'throughput_auto_200' => PrintScheduleSetting::getValue('throughput_auto_200', '350'),
            'throughput_auto_300' => PrintScheduleSetting::getValue('throughput_auto_300', '350'),
            'throughput_auto_370' => PrintScheduleSetting::getValue('throughput_auto_370', '350'),
            'throughput_baby_200' => PrintScheduleSetting::getValue('throughput_baby_200', '180'),
            'throughput_baby_300' => PrintScheduleSetting::getValue('throughput_baby_300', '180'),
            'throughput_baby_370' => PrintScheduleSetting::getValue('throughput_baby_370', '180'),
            'dashboard_notes'     => PrintScheduleSetting::getValue('dashboard_notes',     ''),
        ];

        return view('admin.print-schedule-settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $isMaster = auth()->user()->isMaster();

        $rules = ['dashboard_notes' => 'nullable|string|max:5000'];
        if ($isMaster) {
            $rules = array_merge($rules, [
                'throughput_auto_200' => 'required|integer|min:1',
                'throughput_auto_300' => 'required|integer|min:1',
                'throughput_auto_370' => 'required|integer|min:1',
                'throughput_baby_200' => 'required|integer|min:1',
                'throughput_baby_300' => 'required|integer|min:1',
                'throughput_baby_370' => 'required|integer|min:1',
            ]);
        }

        $request->validate($rules);

        if ($isMaster) {
            PrintScheduleSetting::setValue('throughput_auto_200', (string) $request->integer('throughput_auto_200'));
            PrintScheduleSetting::setValue('throughput_auto_300', (string) $request->integer('throughput_auto_300'));
            PrintScheduleSetting::setValue('throughput_auto_370', (string) $request->integer('throughput_auto_370'));
            PrintScheduleSetting::setValue('throughput_baby_200', (string) $request->integer('throughput_baby_200'));
            PrintScheduleSetting::setValue('throughput_baby_300', (string) $request->integer('throughput_baby_300'));
            PrintScheduleSetting::setValue('throughput_baby_370', (string) $request->integer('throughput_baby_370'));
        }

        PrintScheduleSetting::setValue('dashboard_notes', $request->input('dashboard_notes', ''));

        return back()->with('success', 'Settings saved.');
    }
}
