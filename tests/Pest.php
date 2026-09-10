<?php

use App\Enums\ProfileRole;
use App\Models\Profile;
use App\Models\ProfileMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function userWithProfile(array $user = [], array $profile = [], bool $consent = true): User
{
    $user = User::factory()->create($user);

    Profile::factory()->for($user)->create([
        'name' => $user->name,
        'is_default' => true,
        ...$profile,
    ]);

    if (! $consent) {
        $user->consents()->delete();
    }

    return $user->fresh(['profiles', 'defaultProfile']);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function caregiverFor(Profile $profile, array $user = []): User
{
    $caregiver = userWithProfile($user);

    ProfileMember::query()->create([
        'profile_id' => $profile->id,
        'user_id' => $caregiver->id,
        'email' => mb_strtolower($caregiver->email),
        'role' => ProfileRole::Caregiver,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    return $caregiver->fresh(['profiles', 'defaultProfile']);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registerAttributes(array $overrides = []): array
{
    return [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'timezone' => 'Asia/Kolkata',
        'glucose_unit' => 'mg_dl',
        'weight_unit' => 'kg',
        'accept_terms' => '1',
        'health_data_consent' => '1',
        ...$overrides,
    ];
}
