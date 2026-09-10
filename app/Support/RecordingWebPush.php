<?php

namespace App\Support;

use App\Contracts\SendsWebPush;
use App\Models\PushSubscription;

class RecordingWebPush implements SendsWebPush
{
    /**
     * @var list<array{endpoint: string, payload: array{title: string, body: string, url?: string}}>
     */
    public array $sent = [];

    /**
     * @param  array{title: string, body: string, url?: string}  $payload
     */
    public function send(PushSubscription $subscription, array $payload): bool
    {
        $this->sent[] = [
            'endpoint' => $subscription->endpoint,
            'payload' => $payload,
        ];

        $subscription->forceFill(['last_used_at' => now()])->save();

        return true;
    }
}
