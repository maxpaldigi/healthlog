<?php

use App\Models\Profile;
use App\Models\User;

it('allows a user to manage their own profile and denies others', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $profile = Profile::factory()->for($owner)->create();

    expect($owner->can('view', $profile))->toBeTrue()
        ->and($owner->can('update', $profile))->toBeTrue()
        ->and($owner->can('delete', $profile))->toBeTrue()
        ->and($stranger->can('view', $profile))->toBeFalse()
        ->and($stranger->can('update', $profile))->toBeFalse()
        ->and($stranger->can('delete', $profile))->toBeFalse();
});

it('lets a caregiver view a profile but not delete it', function () {
    $owner = userWithProfile();
    $profile = $owner->defaultProfile;
    $caregiver = caregiverFor($profile);

    expect($caregiver->can('view', $profile))->toBeTrue()
        ->and($caregiver->can('invite', $profile))->toBeTrue()
        ->and($caregiver->can('update', $profile))->toBeFalse()
        ->and($caregiver->can('delete', $profile))->toBeFalse();
});
