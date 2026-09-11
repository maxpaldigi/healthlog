<?php

use App\Actions\Auth\EnsureSuperAdmin;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Policies\UserPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

it('lets only a super admin open the operator dashboard', function () {
    $person = userWithProfile();
    $operator = app(EnsureSuperAdmin::class)->handle();

    $this->get(route('admin.dashboard'))->assertNotFound();

    $this->actingAs($person)
        ->get(route('admin.dashboard'))
        ->assertNotFound();

    $this->actingAs($operator)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->has('totals.users')
            ->has('users')
            ->where('auth.is_super_admin', true));
});

it('does not tell a normal account about the operator dashboard', function () {
    $person = userWithProfile();

    $this->actingAs($person)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->missing('auth.is_super_admin')
            ->where('auth.user', function (mixed $user): bool {
                $row = $user instanceof Collection ? $user->all() : (array) $user;

                return ! array_key_exists('is_super_admin', $row);
            }));
});

it('lists accounts with counts and emails, not reading values', function () {
    $person = userWithProfile(['name' => 'Meena Rao', 'email' => 'meena@example.com']);
    BpReading::factory()->for($person->defaultProfile)->create(['systolic' => 188, 'diastolic' => 94]);
    GlucoseReading::factory()->for($person->defaultProfile)->create(['value_mg_dl' => '312.00']);
    $operator = app(EnsureSuperAdmin::class)->handle();

    $this->actingAs($operator)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->where('totals.users', 2)
            ->where('totals.bp_readings', 1)
            ->where('totals.glucose_readings', 1)
            ->has('users', 2)
            ->where('users', fn ($users): bool => collect($users)->contains(
                fn (array $row): bool => $row['email'] === 'meena@example.com'
                    && $row['name'] === 'Meena Rao'
                    && $row['bp_count'] === 1
                    && $row['glucose_count'] === 1
                    && ! array_key_exists('systolic', $row)
                    && ! array_key_exists('value_mg_dl', $row)
                    && ! array_key_exists('date_of_birth', $row),
            )));
});

it('lets a super admin open the dashboard without health-data consent', function () {
    $operator = app(EnsureSuperAdmin::class)->handle();
    $operator->consents()->delete();

    $this->actingAs($operator)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('sends a super admin to the operator dashboard after a magic link', function () {
    Notification::fake();
    $operator = app(EnsureSuperAdmin::class)->handle();

    $this->post(route('login.store'), [
        'email' => $operator->email,
    ])->assertRedirect(route('login.check-email'));

    $url = Notification::sent($operator, MagicLinkNotification::class)->first()->url;

    $this->get($url)->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($operator);
});

it('lets a super admin view the account directory and denies a stranger', function () {
    $operator = app(EnsureSuperAdmin::class)->handle();
    $person = User::factory()->create();
    $policy = new UserPolicy;

    expect($policy->viewAny($operator))->toBeTrue()
        ->and($policy->viewAny($person))->toBeFalse()
        ->and($policy->view($operator, $person))->toBeTrue()
        ->and($policy->view($person, $operator))->toBeFalse()
        ->and($policy->view($person, $person))->toBeTrue();
});
