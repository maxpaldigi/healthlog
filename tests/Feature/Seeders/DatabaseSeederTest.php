<?php

use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Carbon;

it('seeds a demo user with thirty days of readings', function () {
    $this->seed();

    $user = User::query()->where('email', 'demo@healthlog.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->password)->toBeNull();

    $profile = $user->defaultProfile;

    expect($profile)->not->toBeNull()
        ->and($profile->timezone)->toBe('Asia/Kolkata')
        ->and($profile->is_default)->toBeTrue();

    expect(BpReading::query()->where('profile_id', $profile->id)->count())->toBe(60)
        ->and(GlucoseReading::query()->where('profile_id', $profile->id)->count())->toBe(45)
        ->and(Profile::query()->count())->toBe(1);

    $earliest = BpReading::query()->orderBy('measured_at')->first();
    $latest = BpReading::query()->orderByDesc('measured_at')->first();

    expect($earliest->measured_at->diffInDays($latest->measured_at))->toBeGreaterThanOrEqual(28)
        ->and($latest->measured_at->timezone('Asia/Kolkata')->toDateString())
        ->not->toBe(Carbon::now('Asia/Kolkata')->toDateString());
});
