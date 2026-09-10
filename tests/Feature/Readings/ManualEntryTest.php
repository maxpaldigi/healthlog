<?php

use App\Enums\GlucoseContext;
use App\Enums\GlucoseUnit;
use App\Enums\ReadingTag;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Profile;
use App\Support\Glucose;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the home screen with log buttons, today status, and the safety notice', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('todayCount', 0)
            ->where('todayTarget', 3)
            ->has('todaySlots', 3)
            ->has('recent', 0)
            ->has('profiles', 1)
            ->where('alerts', []));
});

it('hides the profile switcher when the user has one profile', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->has('profiles', 1));
});

it('shows last three readings and today count in the profile timezone', function () {
    $user = userWithProfile();
    $profile = $user->defaultProfile;
    $today = $profile->nowInTimezone()->setTime(8, 0)->utc();
    $yesterday = $profile->nowInTimezone()->subDay()->setTime(8, 0)->utc();

    BpReading::factory()->for($profile)->create(['measured_at' => $today, 'systolic' => 128, 'diastolic' => 82]);
    GlucoseReading::factory()->for($profile)->create(['measured_at' => $today->copy()->setTime(7, 0), 'value_mg_dl' => 110]);
    BpReading::factory()->for($profile)->create(['measured_at' => $yesterday]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('todayCount', 1)
            ->where('todaySlots.0.filled', true)
            ->where('todaySlots.1.filled', false)
            ->where('todaySlots.2.filled', false)
            ->has('recent', 3));
});

it('records a blood pressure reading for the current profile', function () {
    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->subMinutes(10)->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->from(route('bp.create'))
        ->post(route('bp.store'), [
            'systolic' => 132,
            'diastolic' => 84,
            'pulse' => 70,
            'measured_at' => $when,
            'tag' => ReadingTag::BeforeMeds->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $reading = BpReading::query()->first();

    expect($reading)->not->toBeNull()
        ->and($reading->profile_id)->toBe($user->defaultProfile->id)
        ->and($reading->systolic)->toBe(132)
        ->and($reading->diastolic)->toBe(84)
        ->and($reading->pulse)->toBe(70)
        ->and($reading->tag)->toBe(ReadingTag::BeforeMeds)
        ->and($reading->source->value)->toBe('manual');
});

it('rejects invalid blood pressure values', function () {
    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->from(route('bp.create'))
        ->post(route('bp.store'), [
            'systolic' => 80,
            'diastolic' => 90,
            'measured_at' => $when,
            'tag' => ReadingTag::None->value,
        ])
        ->assertRedirect(route('bp.create'))
        ->assertSessionHasErrors(['diastolic']);
});

it('rejects a reading time in the future or older than two years', function () {
    $user = userWithProfile();
    $profile = $user->defaultProfile;

    $this->actingAs($user)
        ->post(route('bp.store'), [
            'systolic' => 120,
            'diastolic' => 80,
            'measured_at' => $profile->nowInTimezone()->addDay()->format('Y-m-d\TH:i'),
            'tag' => ReadingTag::None->value,
        ])
        ->assertSessionHasErrors(['measured_at']);

    $this->actingAs($user)
        ->post(route('bp.store'), [
            'systolic' => 120,
            'diastolic' => 80,
            'measured_at' => $profile->nowInTimezone()->subYears(3)->format('Y-m-d\TH:i'),
            'tag' => ReadingTag::None->value,
        ])
        ->assertSessionHasErrors(['measured_at']);
});

it('pre-selects fasting in the morning on the log sugar screen', function () {
    $this->travelTo(Carbon::parse('2026-09-08 07:15:00', 'Asia/Kolkata'));

    $user = userWithProfile(['email_verified_at' => now()], ['timezone' => 'Asia/Kolkata']);

    $this->actingAs($user)
        ->get(route('glucose.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/LogSugar')
            ->where('defaults.context', GlucoseContext::Fasting->value));

    $this->travelBack();
});

it('records glucose in mg/dL after converting from mmol/L', function () {
    $user = userWithProfile([], ['glucose_unit' => GlucoseUnit::MmolL]);
    $when = $user->defaultProfile->nowInTimezone()->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->post(route('glucose.store'), [
            'value' => '6.00',
            'context' => GlucoseContext::Fasting->value,
            'measured_at' => $when,
        ])
        ->assertRedirect(route('home'));

    $reading = GlucoseReading::query()->first();

    expect($reading)->not->toBeNull()
        ->and((string) $reading->value_mg_dl)->toBe(Glucose::toMgDl('6.00', GlucoseUnit::MmolL))
        ->and($reading->context)->toBe(GlucoseContext::Fasting);
});

it('counts unique blood pressure slots toward the daily target of three', function () {
    $user = userWithProfile();
    $profile = $user->defaultProfile;
    $morning = $profile->nowInTimezone()->setTime(8, 0)->utc();
    $afternoon = $profile->nowInTimezone()->setTime(14, 0)->utc();
    $evening = $profile->nowInTimezone()->setTime(20, 0)->utc();

    BpReading::factory()->for($profile)->create(['measured_at' => $morning]);
    BpReading::factory()->for($profile)->create(['measured_at' => $afternoon]);
    BpReading::factory()->for($profile)->create(['measured_at' => $evening]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('todayCount', 3)
            ->where('todaySlots.0.filled', true)
            ->where('todaySlots.1.filled', true)
            ->where('todaySlots.2.filled', true));
});

it('lets the user filter history with a date range', function () {
    $this->travelTo(Carbon::parse('2026-09-08 12:00:00', 'Asia/Kolkata'));

    $user = userWithProfile([], ['timezone' => 'Asia/Kolkata']);
    $profile = $user->defaultProfile;

    $recent = BpReading::factory()->for($profile)->create([
        'measured_at' => Carbon::parse('2026-09-02 08:00:00', 'Asia/Kolkata')->utc(),
    ]);
    BpReading::factory()->for($profile)->create([
        'measured_at' => Carbon::parse('2026-07-01 08:00:00', 'Asia/Kolkata')->utc(),
    ]);

    $this->actingAs($user)
        ->get(route('history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/History')
            ->where('filters.preset', '30d')
            ->where('filters.range_label', '9 August to 8 September 2026')
            ->has('presets', 3)
            ->has('entries', 1)
            ->where('entries.0.id', $recent->id));

    $this->actingAs($user)
        ->get(route('history', ['from' => '2026-09-01', 'to' => '2026-09-08']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.preset', 'month')
            ->where('month.label', 'September 2026')
            ->where('month.can_next', false)
            ->has('entries', 1));

    $this->actingAs($user)
        ->get(route('history', ['from' => '2026-09-08', 'to' => '2026-09-01']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.from', '2026-09-01')
            ->where('filters.to', '2026-09-08'));

    $this->travelBack();
});

it('lists a merged history and filters by type', function () {
    $user = userWithProfile();
    $profile = $user->defaultProfile;

    $bp = BpReading::factory()->for($profile)->create([
        'measured_at' => $profile->nowInTimezone()->subDay()->setTime(8, 15)->utc(),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'measured_at' => $profile->nowInTimezone()->subHours(2)->utc(),
    ]);

    $this->actingAs($user)
        ->get(route('history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/History')
            ->has('entries', 2));

    $this->actingAs($user)
        ->get(route('history', ['type' => 'bp']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 1)
            ->where('entries.0.id', $bp->id)
            ->where('entries.0.type', 'bp')
            ->where('entries.0.slot', 'morning')
            ->has('entries.0.date_key')
            ->has('entries.0.date_label'));
});

it('lets the owner edit and delete a blood pressure reading', function () {
    $user = userWithProfile();
    $reading = BpReading::factory()->for($user->defaultProfile)->create([
        'systolic' => 130,
        'diastolic' => 80,
        'measured_at' => $user->defaultProfile->nowInTimezone()->subHour()->utc(),
    ]);

    $when = $user->defaultProfile->nowInTimezone()->subHour()->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->put(route('bp.update', $reading), [
            'systolic' => 124,
            'diastolic' => 78,
            'pulse' => 64,
            'measured_at' => $when,
            'tag' => ReadingTag::None->value,
        ])
        ->assertRedirect(route('history'));

    expect($reading->fresh()->systolic)->toBe(124);

    $this->actingAs($user)
        ->delete(route('bp.destroy', $reading))
        ->assertRedirect(route('history'));

    expect(BpReading::query()->find($reading->id))->toBeNull();
});

it('lets the owner edit and delete a glucose reading', function () {
    $user = userWithProfile();
    $reading = GlucoseReading::factory()->for($user->defaultProfile)->create([
        'value_mg_dl' => 140,
        'context' => GlucoseContext::Random,
        'measured_at' => $user->defaultProfile->nowInTimezone()->subHour()->utc(),
    ]);

    $when = $user->defaultProfile->nowInTimezone()->subHour()->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->put(route('glucose.update', $reading), [
            'value' => '118',
            'context' => GlucoseContext::Bedtime->value,
            'measured_at' => $when,
        ])
        ->assertRedirect(route('history'));

    expect((string) $reading->fresh()->value_mg_dl)->toBe('118.00')
        ->and($reading->fresh()->context)->toBe(GlucoseContext::Bedtime);

    $this->actingAs($user)
        ->delete(route('glucose.destroy', $reading))
        ->assertRedirect(route('history'));

    expect(GlucoseReading::query()->find($reading->id))->toBeNull();
});

it('forbids editing another users reading', function () {
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

it('renders the scan page so the user can fall back to manual entry', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->get(route('scan'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('readings/Scan'));
});

it('switches the current profile when the user has more than one', function () {
    $user = userWithProfile();
    $other = Profile::factory()->for($user)->create([
        'name' => 'Parent',
        'is_default' => false,
    ]);

    $this->actingAs($user)
        ->put(route('profiles.current', $other))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('profile.id', $other->id));
});
