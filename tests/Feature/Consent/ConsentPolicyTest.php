<?php

use App\Enums\ConsentType;
use App\Models\Consent;
use App\Models\User;

it('allows a user to view their own consent rows and denies others', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $consent = Consent::factory()->for($owner)->create([
        'type' => ConsentType::HealthData,
    ]);

    expect($owner->can('view', $consent))->toBeTrue()
        ->and($stranger->can('view', $consent))->toBeFalse()
        ->and($owner->can('create', Consent::class))->toBeTrue();
});
