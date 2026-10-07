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
            'throughput_coditherm_200'  => PrintScheduleSetting::getValue('throughput_coditherm_200',  '300'),
            'throughput_coditherm_300'  => PrintScheduleSetting::getValue('throughput_coditherm_300',  '300'),
            'throughput_coditherm_370'  => PrintScheduleSetting::getValue('throughput_coditherm_370',  '300'),
            'throughput_laser_200'      => PrintScheduleSetting::getValue('throughput_laser_200',      '300'),
            'throughput_laser_300'      => PrintScheduleSetting::getValue('throughput_laser_300',      '300'),
            'throughput_laser_370'      => PrintScheduleSetting::getValue('throughput_laser_370',      '300'),
            'throughput_other_200'      => PrintScheduleSetting::getValue('throughput_other_200',      '200'),
            'throughput_other_300'      => PrintScheduleSetting::getValue('throughput_other_300',      '200'),
            'throughput_other_370'      => PrintScheduleSetting::getValue('throughput_other_370',      '200'),
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
                'throughput_coditherm_200' => 'required|integer|min:1',
                'throughput_coditherm_300' => 'required|integer|min:1',
                'throughput_coditherm_370' => 'required|integer|min:1',
                'throughput_laser_200'     => 'required|integer|min:1',
                'throughput_laser_300'     => 'required|integer|min:1',
                'throughput_laser_370'     => 'required|integer|min:1',
                'throughput_other_200'     => 'required|integer|min:1',
                'throughput_other_300'     => 'required|integer|min:1',
                'throughput_other_370'     => 'required|integer|min:1',
            ]);
        }

        $request->validate($rules);

        if ($isMaster) {
            foreach (['auto', 'baby', 'coditherm', 'laser', 'other'] as $group) {
                foreach ([200, 300, 370] as $size) {
                    $key = "throughput_{$group}_{$size}";
                    PrintScheduleSetting::setValue($key, (string) $request->integer($key));
                }
            }
        }

        PrintScheduleSetting::setValue('dashboard_notes', $request->input('dashboard_notes', ''));

        return back()->with('success', 'Settings saved.');
    }
}
