<?php

use App\Contracts\SendsWebPush;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\PushSubscription;
use App\Support\CareAlerts;
use Illuminate\Support\Carbon;

it('flags inactivity when there are no readings for the configured days', function () {
    $this->travelTo(Carbon::parse('2026-09-10 10:00:00', 'UTC'));

    $owner = userWithProfile([], ['timezone' => 'UTC', 'alert_inactivity_days' => 3]);
    $profile = $owner->defaultProfile;
    caregiverFor($profile);

    BpReading::factory()->for($profile)->create([
        'measured_at' => Carbon::parse('2026-09-06 09:00:00', 'UTC'),
        'systolic' => 118,
        'diastolic' => 76,
    ]);

    $alert = app(CareAlerts::class)->inactivity($profile);

    expect($alert)->not->toBeNull()
        ->and($alert['message'])->toStartWith('No readings since');

    $this->travelBack();
});

it('flags three consecutive high blood pressure readings', function () {
    $owner = userWithProfile([], ['timezone' => 'UTC', 'alert_streak' => 3]);
    $profile = $owner->defaultProfile;

    foreach ([1, 2, 3] as $hoursAgo) {
        BpReading::factory()->for($profile)->create([
            'measured_at' => now('UTC')->subHours($hoursAgo),
            'systolic' => 150,
            'diastolic' => 95,
        ]);
    }

    expect(app(CareAlerts::class)->streak($profile)['message'])->toBe('3 readings above target');
});

it('flags three consecutive glucose readings above the profile target', function () {
    $owner = userWithProfile([], [
        'timezone' => 'UTC',
        'alert_streak' => 3,
        'glucose_high_mg_dl' => '140.00',
    ]);
    $profile = $owner->defaultProfile;

    foreach ([1, 2, 3] as $hoursAgo) {
        GlucoseReading::factory()->for($profile)->create([
            'measured_at' => now('UTC')->subHours($hoursAgo),
            'value_mg_dl' => '180.00',
        ]);
    }

    expect(app(CareAlerts::class)->streak($profile)['key'])->toBe(CareAlerts::TYPE_STREAK);
});

it('pushes a caregiver alert once per day and never emails the parent', function () {
    $this->travelTo(Carbon::parse('2026-09-10 10:00:00', 'UTC'));

    $owner = userWithProfile([], ['timezone' => 'UTC', 'alert_inactivity_days' => 3]);
    $profile = $owner->defaultProfile;
    $caregiver = caregiverFor($profile);

    PushSubscription::factory()->for($caregiver->defaultProfile)->create([
        'user_id' => $caregiver->id,
        'endpoint' => 'https://push.example.test/caregiver',
    ]);

    $this->artisan('healthlog:send-care-alerts')->assertSuccessful();

    $push = app(SendsWebPush::class);

    expect($push->sent)->toHaveCount(1)
        ->and($push->sent[0]['payload']['body'])->toContain($profile->name)
        ->and($push->sent[0]['endpoint'])->toBe('https://push.example.test/caregiver');

    $this->artisan('healthlog:send-care-alerts')->assertSuccessful();

    expect($push->sent)->toHaveCount(1);

    $this->travelBack();
});
