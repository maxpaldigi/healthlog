<?php

use App\Contracts\SendsWebPush;
use App\Models\PushSubscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

it('sends a push reminder and no email when a subscription exists', function () {
    Notification::fake();

    $this->travelTo(Carbon::parse('2026-09-08 08:05:00', 'Asia/Kolkata'));

    $user = userWithProfile([], [
        'timezone' => 'Asia/Kolkata',
        'reminders_enabled' => true,
        'reminder_morning' => '08:00',
    ]);

    PushSubscription::factory()->for($user->defaultProfile)->create();

    $this->artisan('healthlog:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();

    $push = app(SendsWebPush::class);

    expect($push->sent)->toHaveCount(1)
        ->and($push->sent[0]['payload']['title'])->toContain('blood pressure')
        ->and($push->sent[0]['payload']['url'])->toBe(route('bp.create'));

    $this->travelBack();
});

it('stores a push subscription for the current profile', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->postJson(route('push-subscriptions.store'), [
            'endpoint' => 'https://push.example.test/device-1',
            'keys' => [
                'p256dh' => str_repeat('A', 87),
                'auth' => str_repeat('B', 22),
            ],
        ])
        ->assertNoContent();

    $this->actingAs($user)
        ->postJson(route('push-subscriptions.store'), [
            'endpoint' => 'https://push.example.test/device-1',
            'keys' => [
                'p256dh' => str_repeat('C', 87),
                'auth' => str_repeat('D', 22),
            ],
        ])
        ->assertNoContent();

    expect($user->defaultProfile->pushSubscriptions()->count())->toBe(1)
        ->and($user->defaultProfile->pushSubscriptions()->first()->p256dh)->toBe(str_repeat('C', 87));
});

it('removes a push subscription by endpoint', function () {
    $user = userWithProfile();
    $subscription = PushSubscription::factory()->for($user->defaultProfile)->create([
        'endpoint' => 'https://push.example.test/device-2',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('push-subscriptions.destroy'), [
            'endpoint' => $subscription->endpoint,
        ])
        ->assertNoContent();

    expect(PushSubscription::query()->find($subscription->id))->toBeNull();
});
