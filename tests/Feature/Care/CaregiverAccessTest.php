<?php

use App\Enums\ProfileRole;
use App\Enums\ReadingTag;
use App\Models\BpReading;
use App\Models\ProfileMember;
use App\Notifications\CaregiverInviteNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

it('lets a caregiver log a reading and forbids deleting the profile', function () {
    $owner = userWithProfile();
    $profile = $owner->defaultProfile;
    $caregiver = caregiverFor($profile);
    $when = $profile->nowInTimezone()->subMinutes(10)->format('Y-m-d\TH:i');

    $this->actingAs($caregiver)
        ->withSession(['current_profile_id' => $profile->id])
        ->post(route('bp.store'), [
            'systolic' => 132,
            'diastolic' => 84,
            'measured_at' => $when,
            'tag' => ReadingTag::None->value,
        ])
        ->assertRedirect(route('home'));

    expect(BpReading::query()->where('profile_id', $profile->id)->count())->toBe(1)
        ->and($caregiver->can('delete', $profile))->toBeFalse()
        ->and($caregiver->can('update', $profile))->toBeFalse();

    $this->actingAs($caregiver)
        ->delete(route('health-profiles.destroy', $profile))
        ->assertForbidden();
});

it('forbids a stranger from editing a reading', function () {
    $owner = userWithProfile();
    $stranger = userWithProfile();
    $reading = BpReading::factory()->for($owner->defaultProfile)->create();

    $this->actingAs($stranger)
        ->put(route('bp.update', $reading), [
            'systolic' => 120,
            'diastolic' => 80,
            'measured_at' => $stranger->defaultProfile->nowInTimezone()->format('Y-m-d\TH:i'),
            'tag' => ReadingTag::None->value,
        ])
        ->assertForbidden();
});

it('sends an invite, accepts it, and shows the helper on the owner home', function () {
    Notification::fake();

    $owner = userWithProfile(['name' => 'Parent']);
    $invitee = userWithProfile(['email' => 'child@example.com']);
    $profile = $owner->defaultProfile;

    $this->actingAs($owner)
        ->post(route('health-profiles.invites.store', $profile), [
            'email' => 'child@example.com',
        ])
        ->assertRedirect();

    Notification::assertSentOnDemand(CaregiverInviteNotification::class);

    $member = ProfileMember::query()
        ->where('profile_id', $profile->id)
        ->where('email', 'child@example.com')
        ->first();

    expect($member)->not->toBeNull()
        ->and($member->accepted_at)->toBeNull()
        ->and($member->role)->toBe(ProfileRole::Caregiver);

    $url = URL::temporarySignedRoute('invites.accept', now()->addDay(), ['member' => $member->id]);

    $this->actingAs($invitee)
        ->get($url)
        ->assertRedirect(route('home'));

    expect($member->fresh()->isAccepted())->toBeTrue()
        ->and($invitee->fresh()->canAccessProfile($profile))->toBeTrue();

    $this->actingAs($owner)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('access.is_owner', true)
            ->has('access.caregivers', 1)
            ->where('access.caregivers.0.email', 'child@example.com'));
});

it('lets the owner revoke caregiver access', function () {
    $owner = userWithProfile();
    $profile = $owner->defaultProfile;
    $caregiver = caregiverFor($profile);
    $member = ProfileMember::query()
        ->where('profile_id', $profile->id)
        ->where('user_id', $caregiver->id)
        ->first();

    $this->actingAs($owner)
        ->delete(route('health-profiles.members.destroy', [$profile, $member]))
        ->assertRedirect();

    expect($caregiver->fresh()->canAccessProfile($profile))->toBeFalse();

    $this->actingAs($caregiver)
        ->withSession(['current_profile_id' => $profile->id])
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('profile.id', $caregiver->defaultProfile->id));
});

it('lists owned and helped logs on the people page, alerts first', function () {
    $owner = userWithProfile(['name' => 'Adult']);
    $profile = $owner->defaultProfile;
    $profile->update(['name' => 'Mum']);
    $caregiver = caregiverFor($profile);

    $this->actingAs($caregiver)
        ->get(route('people'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('People')
            ->has('people', 2));
});
