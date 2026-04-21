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
            'throughput_auto_1' => PrintScheduleSetting::getValue('throughput_auto_1', '350'),
            'throughput_auto_2' => PrintScheduleSetting::getValue('throughput_auto_2', '350'),
            'throughput_auto_3' => PrintScheduleSetting::getValue('throughput_auto_3', '350'),
            'throughput_baby'   => PrintScheduleSetting::getValue('throughput_baby',   '180'),
        ];

        return view('admin.print-schedule-settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'throughput_auto_1' => 'required|integer|min:1',
            'throughput_auto_2' => 'required|integer|min:1',
            'throughput_auto_3' => 'required|integer|min:1',
            'throughput_baby'   => 'required|integer|min:1',
        ]);

        PrintScheduleSetting::setValue('throughput_auto_1', (string) $request->integer('throughput_auto_1'));
        PrintScheduleSetting::setValue('throughput_auto_2', (string) $request->integer('throughput_auto_2'));
        PrintScheduleSetting::setValue('throughput_auto_3', (string) $request->integer('throughput_auto_3'));
        PrintScheduleSetting::setValue('throughput_baby',   (string) $request->integer('throughput_baby'));

        return back()->with('success', 'Settings saved.');
    }
}
