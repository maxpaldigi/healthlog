<?php

namespace App\Console\Commands;

use App\Contracts\SendsWebPush;
use App\Enums\ProfileRole;
use App\Models\Profile;
use App\Models\PushSubscription;
use App\Support\CareAlerts;
use Illuminate\Console\Command;

class SendCareAlerts extends Command
{
    protected $signature = 'healthlog:send-care-alerts';

    protected $description = 'Push inactivity and streak notices to caregivers only';

    public function handle(CareAlerts $alerts, SendsWebPush $push): int
    {
        $sent = 0;

        Profile::query()
            ->whereHas('members', function ($query): void {
                $query->whereNotNull('accepted_at')->where('role', ProfileRole::Caregiver);
            })
            ->with(['members' => fn ($query) => $query->whereNotNull('accepted_at')->where('role', ProfileRole::Caregiver)->with('user')])
            ->each(function (Profile $profile) use ($alerts, $push, &$sent): void {
                foreach ($alerts->dueToSend($profile) as $alert) {
                    $delivered = false;

                    foreach ($profile->members as $member) {
                        $user = $member->user;

                        if ($user === null) {
                            continue;
                        }

                        $subscriptions = PushSubscription::query()
                            ->where('user_id', $user->id)
                            ->get();

                        foreach ($subscriptions as $subscription) {
                            if ($push->send($subscription, [
                                'title' => 'HealthyMe',
                                'body' => $alert['message'].' for '.$profile->name.'.',
                                'url' => route('people'),
                            ])) {
                                $delivered = true;
                            }
                        }
                    }

                    if ($delivered) {
                        $alerts->markSent($profile, $alert['key']);
                        $sent++;
                        $this->info($profile->name.': '.$alert['message']);
                    }
                }
            });

        $this->info('Sent '.$sent.' caregiver alert'.($sent === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }
}
