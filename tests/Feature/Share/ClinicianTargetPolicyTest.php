<?php

use App\Models\ClinicianTarget;
use App\Models\ShareLink;
use App\Models\User;

it('lets a profile member view a clinic note and denies a stranger', function () {
    $owner = userWithProfile();
    $profile = $owner->defaultProfile;
    $caregiver = caregiverFor($profile);
    $stranger = User::factory()->create();
    $link = $profile->shareLinks()->create([
        'token' => ShareLink::makeToken(),
        'starts_on' => now()->toDateString(),
        'ends_on' => now()->toDateString(),
        'expires_at' => now()->addDay(),
    ]);
    $note = ClinicianTarget::query()->create([
        'profile_id' => $profile->id,
        'share_link_id' => $link->id,
        'instruction' => 'Log sugar before breakfast.',
        'acknowledged_at' => now(),
    ]);

    expect($owner->can('view', $note))->toBeTrue()
        ->and($caregiver->can('view', $note))->toBeTrue()
        ->and($stranger->can('view', $note))->toBeFalse();
});
