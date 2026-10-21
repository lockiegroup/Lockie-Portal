<?php

namespace App\Console\Commands;

use App\Mail\CrmFollowUpMail;
use App\Models\KeyAccountContact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCrmFollowUpReminders extends Command
{
    protected $signature   = 'crm:send-followup-reminders';
    protected $description = 'Email users when their CRM follow-up reminders are due today';

    public function handle(): int
    {
        $contacts = KeyAccountContact::with(['account', 'user'])
            ->whereDate('follow_up_at', today())
            ->get();

        if ($contacts->isEmpty()) {
            $this->info('No follow-ups due today.');
            return 0;
        }

        $sent = 0;

        foreach ($contacts as $contact) {
            $email = $contact->user?->email;
            if (!$email) continue;

            Mail::to($email)->send(new CrmFollowUpMail($contact));
            $sent++;
            $this->line("Sent to {$email} for account {$contact->account?->account_code}");
        }

        $this->info("Done. {$sent} reminder(s) sent.");
        return 0;
    }
}
