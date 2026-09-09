<?php

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\Profile;
use Inertia\Testing\AssertableInertia as Assert;

it('lets the user edit timezone, units, sugar targets, and reminders', function () {
    $user = userWithProfile();
    $profile = $user->defaultProfile;

    $this->actingAs($user)
        ->get(route('health-profiles.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/HealthProfiles')
            ->where('current.id', $profile->id));

    $this->actingAs($user)
        ->patch(route('health-profiles.update', $profile), [
            'name' => 'Dad',
            'timezone' => 'America/New_York',
            'glucose_unit' => GlucoseUnit::MgDl->value,
            'weight_unit' => WeightUnit::Lb->value,
            'glucose_low' => '70',
            'glucose_high' => '180',
            'reminders_enabled' => '1',
            'reminder_morning' => '07:30',
            'reminder_afternoon' => '13:30',
            'reminder_evening' => '20:30',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('health-profiles.edit'));

    $profile->refresh();

    expect($profile->name)->toBe('Dad')
        ->and($profile->timezone)->toBe('America/New_York')
        ->and($profile->weight_unit)->toBe(WeightUnit::Lb)
        ->and((string) $profile->glucose_low_mg_dl)->toBe('70.00')
        ->and($profile->reminders_enabled)->toBeTrue()
        ->and($profile->reminder_morning)->toBe('07:30');
});

it('adds another health profile for a parent', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->post(route('health-profiles.store'), [
            'name' => 'Amma',
            'timezone' => 'Asia/Kolkata',
            'glucose_unit' => GlucoseUnit::MgDl->value,
            'weight_unit' => WeightUnit::Kg->value,
        ])
        ->assertRedirect(route('health-profiles.edit'));

    expect($user->profiles()->count())->toBe(2)
        ->and($user->profiles()->where('name', 'Amma')->exists())->toBeTrue();
});

it('will not delete the last health profile', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->delete(route('health-profiles.destroy', $user->defaultProfile))
        ->assertStatus(422);
});

it('deletes an extra health profile', function () {
    $user = userWithProfile();
    $extra = Profile::factory()->for($user)->create([
        'name' => 'Parent',
        'is_default' => false,
    ]);

    $this->actingAs($user)
        ->withSession(['current_profile_id' => $extra->id])
        ->delete(route('health-profiles.destroy', $extra))
        ->assertRedirect(route('health-profiles.edit'));

    expect(Profile::query()->find($extra->id))->toBeNull()
        ->and($user->profiles()->count())->toBe(1);
});
