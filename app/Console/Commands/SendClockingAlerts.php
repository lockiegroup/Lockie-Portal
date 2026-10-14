<?php

namespace App\Console\Commands;

use App\Mail\ClockingAbsenceAlert;
use App\Models\ClockingAlertLog;
use App\Models\ClockingAlertRecipient;
use App\Models\ClockingEmployee;
use App\Models\ClockingPunch;
use App\Models\ClockingSettingRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendClockingAlerts extends Command
{
    protected $signature   = 'clocking:send-alerts';
    protected $description = 'Email alert recipients when an employee has not clocked in by their scheduled start';

    public function handle(): void
    {
        $tz      = config('app.timezone');
        $now     = now($tz);
        $today   = $now->format('Y-m-d');
        $dayKey  = strtolower($now->format('D')); // mon, tue...
        $graceMins = (int) (ClockingSettingRecord::where('key', 'alert_grace_minutes')->value('value') ?? 15);

        // Global recipients (category_id = null)
        $globalEmails = ClockingAlertRecipient::whereNull('category_id')->pluck('email')->toArray();

        $employees = ClockingEmployee::with(['category.alertRecipients'])
            ->where('is_active', true)
            ->get();

        foreach ($employees as $emp) {
            $schedule = $emp->schedule ?? [];
            $daySchedule = $schedule[$dayKey] ?? [];
            if (empty($daySchedule['start'])) continue; // not scheduled today

            // Parse scheduled start and add grace period
            $scheduledStart = Carbon::createFromFormat('H:i', $daySchedule['start'], $tz)->setDateFrom($now);
            $alertAfter     = $scheduledStart->copy()->addMinutes($graceMins);
            if ($now->lt($alertAfter)) continue; // not yet time to alert

            // Already alerted today?
            if (ClockingAlertLog::where('employee_id', $emp->id)->where('alert_date', $today)->exists()) continue;

            // On absent override?
            if (\App\Models\ClockingAbsentOverride::where('employee_id', $emp->id)->where('date', $today)->exists()) continue;

            // Already clocked in today?
            $clockedIn = ClockingPunch::where('employee_id', $emp->id)
                ->where('direction', 'in')
                ->whereNull('voided_at')
                ->whereDate('punched_at', $today)
                ->exists();
            if ($clockedIn) continue;

            // Build recipient list: category emails + global
            $categoryEmails = $emp->category?->alertRecipients->pluck('email')->toArray() ?? [];
            $recipients     = array_unique(array_merge($categoryEmails, $globalEmails));
            if (empty($recipients)) continue;

            $mailable = new ClockingAbsenceAlert(
                employee:       $emp,
                scheduledStart: $daySchedule['start'],
                currentTime:    $now->format('H:i'),
                date:           $now->format('l, j F Y'),
            );

            foreach ($recipients as $email) {
                Mail::to($email)->send($mailable);
            }

            ClockingAlertLog::create([
                'employee_id' => $emp->id,
                'alert_date'  => $today,
                'sent_at'     => now(),
                'recipients'  => $recipients,
            ]);

            $this->line("Alert sent for {$emp->name} → " . implode(', ', $recipients));
        }

        $this->info('Done.');
    }
}
