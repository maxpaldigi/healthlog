<?php

use App\Enums\ConsentType;
use App\Enums\ShareLinkScope;
use App\Models\Consent;
use App\Models\ShareLink;
use App\Models\User;
use App\Policies\ShareLinkPolicy;

it('writes a share-link consent row when a share link is created', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->post(route('share-links.store'), [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
            'days' => 7,
        ])
        ->assertRedirect()
        ->assertSessionHas('share_url');

    expect(ShareLink::query()->count())->toBe(1);

    $consent = Consent::query()
        ->where('user_id', $user->id)
        ->where('type', ConsentType::ShareLink)
        ->first();

    expect($consent)->not->toBeNull()
        ->and($consent->revoked_at)->toBeNull()
        ->and($consent->policy_version)->toBe(Consent::POLICY_VERSION);
});

it('allows an owner to create a share link and denies a stranger', function () {
    $owner = userWithProfile();
    $stranger = User::factory()->create();
    $profile = $owner->defaultProfile;

    expect($owner->can('create', [ShareLink::class, $profile]))->toBeTrue()
        ->and($stranger->can('create', [ShareLink::class, $profile]))->toBeFalse();
});

it('allows a guest to respond only on an unexpired reply link', function () {
    $owner = userWithProfile();
    $profile = $owner->defaultProfile;

    $respond = $profile->shareLinks()->create([
        'token' => ShareLink::makeToken(),
        'scope' => ShareLinkScope::Respond,
        'starts_on' => now()->toDateString(),
        'ends_on' => now()->toDateString(),
        'expires_at' => now()->addDay(),
    ]);
    $read = $profile->shareLinks()->create([
        'token' => ShareLink::makeToken(),
        'scope' => ShareLinkScope::Read,
        'starts_on' => now()->toDateString(),
        'ends_on' => now()->toDateString(),
        'expires_at' => now()->addDay(),
    ]);

    $policy = new ShareLinkPolicy;

    expect($policy->respond(null, $respond))->toBeTrue()
        ->and($policy->respond(null, $read))->toBeFalse()
        ->and($owner->can('respond', $respond))->toBeTrue();
});
