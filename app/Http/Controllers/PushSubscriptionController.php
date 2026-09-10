<?php

namespace App\Http\Controllers;

use App\Actions\Push\StorePushSubscription;
use App\Http\Requests\Push\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use App\Support\CurrentProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PushSubscriptionController extends Controller
{
    public function store(
        StorePushSubscriptionRequest $request,
        CurrentProfile $currentProfile,
        StorePushSubscription $storePushSubscription,
    ): Response {
        $profile = $currentProfile->handle($request->user(), $request);

        $this->authorize('create', [PushSubscription::class, $profile]);

        /** @var array{endpoint: string, keys: array{p256dh: string, auth: string}} $data */
        $data = $request->validated();

        $storePushSubscription->handle($profile, $data, $request->user(), $request->userAgent());

        return response()->noContent();
    }

    public function destroy(Request $request, CurrentProfile $currentProfile): Response
    {
        $profile = $currentProfile->handle($request->user(), $request);

        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
        ]);

        $subscription = $profile->pushSubscriptions()
            ->where('endpoint', $validated['endpoint'])
            ->first();

        if ($subscription !== null) {
            $this->authorize('delete', $subscription);
            $subscription->delete();
        }

        return response()->noContent();
    }
}
