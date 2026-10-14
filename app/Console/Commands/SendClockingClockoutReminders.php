<?php

namespace App\Console\Commands;

use App\Mail\ClockingClockoutAlert;
use App\Models\ClockingAlertLog;
use App\Models\ClockingAlertRecipient;
use App\Models\ClockingEmployee;
use App\Models\ClockingPunch;
use App\Models\ClockingSettingRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendClockingClockoutReminders extends Command
{
    protected $signature   = 'clocking:send-clockout-reminders';
    protected $description = 'Email alert recipients when an employee has not clocked out after their scheduled end time';

    public function handle(): void
    {
        $tz      = config('app.timezone');
        $now     = now($tz);
        $today   = $now->format('Y-m-d');
        $dayKey  = strtolower($now->format('D'));
        $graceMins = (int) (ClockingSettingRecord::where('key', 'alert_grace_minutes')->value('value') ?? 15);

        $globalEmails = ClockingAlertRecipient::whereNull('category_id')->pluck('email')->toArray();

        $employees = ClockingEmployee::with(['category.alertRecipients'])
            ->where('is_active', true)
            ->get();

        foreach ($employees as $emp) {
            $schedule    = $emp->schedule ?? [];
            $daySchedule = $schedule[$dayKey] ?? [];

            // Must have a scheduled end time
            if (empty($daySchedule['end'])) continue;

            $scheduledEnd = Carbon::createFromFormat('H:i', $daySchedule['end'], $tz)->setDateFrom($now);
            $alertAfter   = $scheduledEnd->copy()->addMinutes($graceMins);
            if ($now->lt($alertAfter)) continue;

            // Already sent a clockout reminder today?
            if (ClockingAlertLog::where('employee_id', $emp->id)
                ->where('alert_date', $today)
                ->where('alert_type', 'clockout')
                ->exists()) continue;

            // Check: employee must still be clocked in (last non-voided punch today is 'in')
            $lastPunch = ClockingPunch::where('employee_id', $emp->id)
                ->whereNull('voided_at')
                ->whereDate('punched_at', $today)
                ->orderByDesc('punched_at')
                ->first();

            if (!$lastPunch || $lastPunch->direction !== 'in') continue;

            $categoryEmails = $emp->category?->alertRecipients->pluck('email')->toArray() ?? [];
            $recipients     = array_unique(array_merge($categoryEmails, $globalEmails));
            if (empty($recipients)) continue;

            $mailable = new ClockingClockoutAlert(
                employee:      $emp,
                scheduledEnd:  $daySchedule['end'],
                currentTime:   $now->format('H:i'),
                date:          $now->format('l, j F Y'),
                clockedInAt:   $lastPunch->punched_at->format('H:i'),
            );

            foreach ($recipients as $email) {
                Mail::to($email)->send($mailable);
            }

            ClockingAlertLog::create([
                'employee_id' => $emp->id,
                'alert_type'  => 'clockout',
                'alert_date'  => $today,
                'sent_at'     => now(),
                'recipients'  => $recipients,
            ]);

            $this->line("Clockout reminder sent for {$emp->name} → " . implode(', ', $recipients));
        }

        $this->info('Done.');
    }
}
