<?php

use App\Models\Hba1cResult;
use App\Models\Medication;
use App\Models\Weight;
use Inertia\Testing\AssertableInertia as Assert;

it('records weight, hba1c, and medications for the current profile', function () {
    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->subHour()->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->get(route('clinic.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('clinic/Clinic'));

    $this->actingAs($user)
        ->post(route('weights.store'), [
            'value' => '72.5',
            'measured_at' => $when,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('clinic.index'));

    $this->actingAs($user)
        ->post(route('hba1c.store'), [
            'value' => '6.8',
            'tested_at' => $user->defaultProfile->nowInTimezone()->toDateString(),
            'lab_name' => 'City Lab',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('clinic.index'));

    $this->actingAs($user)
        ->post(route('medications.store'), [
            'name' => 'Metformin',
            'dose' => '500 mg',
            'schedule' => 'Morning',
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('clinic.index'));

    expect(Weight::query()->first()->profile_id)->toBe($user->defaultProfile->id)
        ->and((string) Hba1cResult::query()->first()->value)->toBe('6.80')
        ->and(Medication::query()->first()->name)->toBe('Metformin');
});

it('includes clinic extras on the visit report', function () {
    $user = userWithProfile();
    $profile = $user->defaultProfile;

    $profile->weights()->create([
        'kg' => 70,
        'measured_at' => $profile->nowInTimezone()->subDay()->utc(),
    ]);
    $profile->hba1cResults()->create([
        'value' => 7.1,
        'tested_at' => $profile->nowInTimezone()->toDateString(),
        'lab_name' => 'Home kit',
    ]);
    $profile->medications()->create([
        'name' => 'Amlodipine',
        'dose' => '5 mg',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('report'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.weights.0.value', '70.00')
            ->where('report.hba1c.0.value', '7.10')
            ->where('report.medications.0.name', 'Amlodipine'));
});
