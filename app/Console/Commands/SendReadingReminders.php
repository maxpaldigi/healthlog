<?php

namespace App\Console\Commands;

use App\Contracts\SendsWebPush;
use App\Enums\BpDaySlot;
use App\Models\Profile;
use App\Notifications\SlotReminderNotification;
use App\Support\ReadingReminders;
use Illuminate\Console\Command;

class SendReadingReminders extends Command
{
    protected $signature = 'healthlog:send-reminders';

    protected $description = 'Send morning, afternoon, and evening log reminders by push, with email as fallback';

    public function handle(ReadingReminders $reminders, SendsWebPush $push): int
    {
        $sent = 0;

        Profile::query()
            ->where('reminders_enabled', true)
            ->with(['user', 'pushSubscriptions'])
            ->each(function (Profile $profile) use ($reminders, $push, &$sent): void {
                $user = $profile->user;

                if ($user === null || blank($user->email)) {
                    return;
                }

                foreach ($reminders->dueSlots($profile) as $slot) {
                    if (! $this->deliver($profile, $slot, $push)) {
                        $user->notify(new SlotReminderNotification($profile, $slot));
                    }

                    $reminders->markSent($profile, $slot);
                    $sent++;
                    $this->info('Reminded '.$user->email.' for '.$profile->name.' '.$slot->value);
                }
            });

        $this->info('Sent '.$sent.' reminder'.($sent === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }

    private function deliver(Profile $profile, BpDaySlot $slot, SendsWebPush $push): bool
    {
        $subscriptions = $profile->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $payload = [
            'title' => 'Time to log '.strtolower($slot->label()).' blood pressure',
            'body' => 'Open HealthyMe to add a reading for '.$profile->name.'.',
            'url' => route('bp.create'),
        ];

        $delivered = false;

        foreach ($subscriptions as $subscription) {
            if ($push->send($subscription, $payload)) {
                $delivered = true;
            }
        }

        return $delivered;
    }
}
