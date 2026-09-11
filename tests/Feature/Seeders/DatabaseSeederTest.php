<?php

use App\Enums\ConsentType;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Carbon;

it('does not seed a demo user in production', function () {
    $this->app['env'] = 'production';

    $this->seed();

    expect(User::query()->where('email', 'demo@healthlog.test')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'maxpal1234@gmail.com')->where('is_super_admin', true)->exists())->toBeTrue()
        ->and(Profile::query()->count())->toBe(0)
        ->and(BpReading::query()->count())->toBe(0);
});

it('seeds a demo user with thirty days of readings', function () {
    $this->seed();

    $user = User::query()->where('email', 'demo@healthlog.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->password)->toBeNull()
        ->and($user->hasGrantedConsent(ConsentType::HealthData))->toBeFalse();

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
