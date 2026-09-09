<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Notifications\SlotReminderNotification;
use App\Support\ReadingReminders;
use Illuminate\Console\Command;

class SendReadingReminders extends Command
{
    protected $signature = 'healthlog:send-reminders';

    protected $description = 'Email morning, afternoon, and evening log reminders in each profile timezone';

    public function handle(ReadingReminders $reminders): int
    {
        $sent = 0;

        Profile::query()
            ->where('reminders_enabled', true)
            ->with('user')
            ->each(function (Profile $profile) use ($reminders, &$sent): void {
                $user = $profile->user;

                if ($user === null || $user->email === null) {
                    return;
                }

                foreach ($reminders->dueSlots($profile) as $slot) {
                    $user->notify(new SlotReminderNotification($profile, $slot));
                    $reminders->markSent($profile, $slot);
                    $sent++;
                    $this->info('Reminded '.$user->email.' for '.$profile->name.' '.$slot->value);
                }
            });

        $this->info('Sent '.$sent.' reminder'.($sent === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }
}
