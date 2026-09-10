<?php

namespace App\Support;

use App\Contracts\SendsWebPush;
use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class SendWebPush implements SendsWebPush
{
    /**
     * @param  array{title: string, body: string, url?: string}  $payload
     */
    public function send(PushSubscription $subscription, array $payload): bool
    {
        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');
        $subject = config('webpush.vapid.subject');

        if (blank($publicKey) || blank($privateKey) || blank($subject)) {
            return false;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => (string) $subject,
                    'publicKey' => (string) $publicKey,
                    'privateKey' => (string) $privateKey,
                ],
            ]);

            $report = $webPush->sendOneNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->p256dh,
                    'authToken' => $subscription->auth,
                ]),
                json_encode($payload, JSON_THROW_ON_ERROR),
            );
        } catch (Throwable) {
            return false;
        }

        if ($report->isSubscriptionExpired()) {
            $subscription->delete();

            return false;
        }

        if (! $report->isSuccess()) {
            return false;
        }

        $subscription->forceFill(['last_used_at' => now()])->save();

        return true;
    }
}
