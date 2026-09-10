<?php

use App\Enums\GlucoseContext;
use App\Models\BpReading;
use App\Models\FoodLog;
use App\Models\GlucoseReading;
use App\Models\Profile;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

function seedFoodPairs(Profile $profile, string $label, array $mgDl): void
{
    foreach (array_values($mgDl) as $index => $value) {
        $day = Carbon::parse('2026-08-01', $profile->clockTimezone())->addDays($index);
        $reading = GlucoseReading::factory()->for($profile)->create([
            'context' => GlucoseContext::PostMeal2h,
            'value_mg_dl' => $value,
            'measured_at' => $day->copy()->setTime(14, 0)->utc(),
        ]);
        FoodLog::factory()->for($profile)->create([
            'label' => $label,
            'eaten_at' => $day->copy()->setTime(12, 0)->utc(),
            'glucose_reading_id' => $reading->id,
        ]);
    }
}

it('hides food averages on home and trends until five samples', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    seedFoodPairs($profile, 'Rice', ['210.00', '210.00', '210.00', '210.00']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('foodResponse.after_meals', []));

    $this->actingAs($user)
        ->get(route('trends'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/Trends')
            ->where('foodResponse.after_meals', [])
            ->where('foodResponse.hint', 'After 5 times of the same food with a 2-hour sugar reading, a comparison appears here.'));
});

it('shows food averages on home and trends after five linked 2-hour readings', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    seedFoodPairs($profile, 'Rice', array_fill(0, 7, '210.00'));
    seedFoodPairs($profile, 'Roti', array_fill(0, 9, '155.00'));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('foodResponse.after_meals.0.line', 'After Rice: 210 mg/dL avg (7 readings).')
            ->where('foodResponse.after_meals.1.line', 'After Roti: 155 mg/dL avg (9 readings).'));

    $this->actingAs($user)
        ->get(route('trends'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/Trends')
            ->where('foodResponse.after_meals.0.line', 'After Rice: 210 mg/dL avg (7 readings).')
            ->where('foodResponse.intro', 'Your numbers after these foods. Check with your clinic.'));
});

it('shows next-morning blood pressure averages on trends', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    foreach ([130, 132, 134, 128, 136, 132] as $index => $systolic) {
        $evening = Carbon::parse('2026-08-01 20:00:00', 'UTC')->addDays($index);
        FoodLog::factory()->for($profile)->create([
            'label' => 'Roti',
            'eaten_at' => $evening,
        ]);
        BpReading::factory()->for($profile)->create([
            'systolic' => $systolic,
            'diastolic' => 80,
            'measured_at' => $evening->copy()->addHours(12),
        ]);
    }

    $this->actingAs($user)
        ->get(route('trends'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('foodResponse.morning_blood_pressure.0.line', 'After Roti: morning 132 avg (6 readings).'));
});

it('shows the current profile food averages to a caregiver', function () {
    $owner = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $owner->defaultProfile;
    $caregiver = caregiverFor($profile);

    seedFoodPairs($profile, 'Rice', array_fill(0, 5, '210.00'));

    $this->actingAs($caregiver)
        ->withSession(['current_profile_id' => $profile->id])
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('foodResponse.after_meals.0.line', 'After Rice: 210 mg/dL avg (5 readings).'));
});
