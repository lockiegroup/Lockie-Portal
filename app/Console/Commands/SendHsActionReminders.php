<?php

namespace App\Console\Commands;

use App\Mail\HsActionReminderMail;
use App\Models\HsAction;
use App\Models\HsSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendHsActionReminders extends Command
{
    protected $signature   = 'hs:send-reminders';
    protected $description = 'Mark overdue H&S actions and send reminder emails to configured recipients';

    public function handle(): int
    {
        // Mark open/in_progress actions as overdue if past due date
        $markedOverdue = HsAction::whereIn('status', ['open', 'in_progress'])
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $this->info("Marked {$markedOverdue} action(s) as overdue.");

        // Fetch recipients from settings
        $recipientIds = HsSetting::get('action_reminder_recipients', []);
        if (empty($recipientIds)) {
            $this->info('No reminder recipients configured — skipping email.');
            return self::SUCCESS;
        }

        $daysBefore = (int) (HsSetting::get('action_reminder_days_before', 3) ?? 3);

        $overdue = HsAction::with('assignedUser')
            ->where('status', 'overdue')
            ->orderBy('due_date')
            ->get();

        $dueSoon = HsAction::with('assignedUser')
            ->whereNotIn('status', ['completed', 'overdue'])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays($daysBefore)->toDateString()])
            ->orderBy('due_date')
            ->get();

        if ($overdue->isEmpty() && $dueSoon->isEmpty()) {
            $this->info('No overdue or upcoming actions — no email sent.');
            return self::SUCCESS;
        }

        $recipients = User::whereIn('id', $recipientIds)->where('is_active', true)->get();

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new HsActionReminderMail($dueSoon, $overdue, $daysBefore));
            $this->info("Reminder sent to {$recipient->email}");
        }

        return self::SUCCESS;
    }
}
