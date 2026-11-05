<?php

namespace App\Console\Commands;

use App\Mail\ClockingAutoClockOutMail;
use App\Models\ClockingAlertRecipient;
use App\Models\ClockingEmployee;
use App\Models\ClockingPunch;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class AutoClockOut extends Command
{
    protected $signature   = 'clocking:auto-clockout';
    protected $description = 'Auto clock out employees still clocked in at midnight and email their managers';

    public function handle(): void
    {
        $tz    = config('app.timezone');
        $today = now($tz)->startOfDay();

        $globalEmails = ClockingAlertRecipient::whereNull('category_id')->pluck('email')->toArray();

        $employees = ClockingEmployee::with(['category.alertRecipients'])
            ->where('is_active', true)
            ->get();

        // employee_id => last non-voided punch
        $lastPunches = ClockingPunch::whereIn('employee_id', $employees->pluck('id'))
            ->whereNull('voided_at')
            ->where('punched_at', '<', $today)
            ->orderBy('punched_at')
            ->get()
            ->groupBy('employee_id')
            ->map(fn($punches) => $punches->last());

        // Group affected employees by category for the summary email
        $byCategory = [];

        foreach ($employees as $emp) {
            $last = $lastPunches->get($emp->id);

            if (!$last || $last->direction !== 'in') continue;

            // Create auto clock-out at 23:59:59 of the day they clocked in
            $clockOutAt = Carbon::parse($last->punched_at, $tz)
                ->endOfDay()
                ->setTimezone('UTC');

            ClockingPunch::create([
                'employee_id' => $emp->id,
                'direction'   => 'out',
                'punched_at'  => $clockOutAt,
                'source'      => 'auto',
                'auto_closed' => true,
            ]);

            $catId = $emp->category_id ?? 'none';
            $byCategory[$catId]['recipients'] = array_unique(array_merge(
                $emp->category?->alertRecipients->pluck('email')->toArray() ?? [],
                $globalEmails,
            ));
            $byCategory[$catId]['category'] = $emp->category;
            $byCategory[$catId]['employees'][] = [
                'name'          => $emp->name,
                'clocked_in_at' => Carbon::parse($last->punched_at, $tz)->format('H:i'),
                'date'          => Carbon::parse($last->punched_at, $tz)->format('l, j F Y'),
            ];

            $this->line("Auto clocked out: {$emp->name} (clocked in {$last->punched_at->format('H:i')})");
        }

        foreach ($byCategory as $group) {
            if (empty($group['recipients'])) continue;

            $mailable = new ClockingAutoClockOutMail(
                category:  $group['category'],
                employees: $group['employees'],
            );

            foreach ($group['recipients'] as $email) {
                Mail::to($email)->send($mailable);
            }

            $this->line('Email sent to: ' . implode(', ', $group['recipients']));
        }

        $this->info('Auto clock-out complete.');
    }
}
