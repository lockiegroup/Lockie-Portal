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
            'throughput_auto_200'       => PrintScheduleSetting::getValue('throughput_auto_200',       '350'),
            'throughput_auto_300'       => PrintScheduleSetting::getValue('throughput_auto_300',       '350'),
            'throughput_auto_370'       => PrintScheduleSetting::getValue('throughput_auto_370',       '350'),
            'throughput_baby_200'       => PrintScheduleSetting::getValue('throughput_baby_200',       '180'),
            'throughput_baby_300'       => PrintScheduleSetting::getValue('throughput_baby_300',       '180'),
            'throughput_baby_370'       => PrintScheduleSetting::getValue('throughput_baby_370',       '180'),
            'throughput_coditherm'      => PrintScheduleSetting::getValue('throughput_coditherm',      '300'),
            'throughput_laser'          => PrintScheduleSetting::getValue('throughput_laser',          '300'),
            'throughput_other'          => PrintScheduleSetting::getValue('throughput_other',          '200'),
            'dashboard_notes'           => PrintScheduleSetting::getValue('dashboard_notes',           ''),
        ];

        return view('admin.print-schedule-settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $isMaster = auth()->user()->isMaster();

        $rules = ['dashboard_notes' => 'nullable|string|max:5000'];
        if ($isMaster) {
            $rules = array_merge($rules, [
                'throughput_auto_200'      => 'required|integer|min:1',
                'throughput_auto_300'      => 'required|integer|min:1',
                'throughput_auto_370'      => 'required|integer|min:1',
                'throughput_baby_200'      => 'required|integer|min:1',
                'throughput_baby_300'      => 'required|integer|min:1',
                'throughput_baby_370'      => 'required|integer|min:1',
                'throughput_coditherm'     => 'required|integer|min:1',
                'throughput_laser'         => 'required|integer|min:1',
                'throughput_other'         => 'required|integer|min:1',
            ]);
        }

        $request->validate($rules);

        if ($isMaster) {
            foreach (['auto', 'baby'] as $group) {
                foreach ([200, 300, 370] as $size) {
                    $key = "throughput_{$group}_{$size}";
                    PrintScheduleSetting::setValue($key, (string) $request->integer($key));
                }
            }
            foreach (['coditherm', 'laser', 'other'] as $group) {
                PrintScheduleSetting::setValue("throughput_{$group}", (string) $request->integer("throughput_{$group}"));
            }
        }

        PrintScheduleSetting::setValue('dashboard_notes', $request->input('dashboard_notes', ''));

        return back()->with('success', 'Settings saved.');
    }
}
