<?php

namespace App\Contracts;

use App\Models\PushSubscription;

interface SendsWebPush
{
    /**
     * @param  array{title: string, body: string, url?: string}  $payload
     */
    public function send(PushSubscription $subscription, array $payload): bool;
}
