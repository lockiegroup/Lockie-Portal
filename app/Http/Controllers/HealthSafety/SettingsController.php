<?php

namespace App\Http\Controllers\HealthSafety;

use App\Http\Controllers\Controller;
use App\Models\HsSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $users             = User::where('is_active', true)->orderBy('name')->get();
        $recipientIds      = HsSetting::get('action_reminder_recipients', []);
        $reminderDaysBefore = (int) (HsSetting::get('action_reminder_days_before', 3) ?? 3);

        return view('health-safety.settings.index', compact('users', 'recipientIds', 'reminderDaysBefore'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'recipient_ids'       => 'nullable|array',
            'recipient_ids.*'     => 'exists:users,id',
            'reminder_days_before' => 'required|integer|min:1|max:30',
        ]);

        HsSetting::set('action_reminder_recipients', $request->input('recipient_ids', []));
        HsSetting::set('action_reminder_days_before', $request->integer('reminder_days_before'));

        return back()->with('success', 'Settings saved.');
    }
}
