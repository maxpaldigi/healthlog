<?php

namespace App\Actions\Push;

use App\Models\Profile;
use App\Models\PushSubscription;

class StorePushSubscription
{
    /**
     * @param  array{endpoint: string, keys: array{p256dh: string, auth: string}}  $data
     */
    public function handle(Profile $profile, array $data, ?string $userAgent = null): PushSubscription
    {
        return $profile->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'user_agent' => $userAgent,
            ],
        );
    }
}
