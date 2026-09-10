<?php

use App\Enums\GlucoseContext;
use App\Enums\ReadingTag;
use Inertia\Testing\AssertableInertia as Assert;

it('applies large text on the document when the cookie is set', function () {
    $response = $this->withUnencryptedCookie('large_text', '1')->get('/');

    $response->assertOk();
    expect($response->getContent())->toMatch('/<html[^>]*\blarge-text\b/');
});

it('does not apply large text by default', function () {
    $response = $this->get('/');

    $response->assertOk();
    expect($response->getContent())->not->toMatch('/<html[^>]*\blarge-text\b/');
});

it('shows appearance settings including large text and read aloud', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/Appearance'));
});

it('includes a spoken home summary', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('summarySpeech', '0 of 3 blood pressure readings today. Morning not yet. Afternoon not yet. Evening not yet.'));
});

it('flashes spoken numbers after saving a blood pressure reading', function () {
    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->subMinutes(10)->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->post(route('bp.store'), [
            'systolic' => 132,
            'diastolic' => 84,
            'pulse' => 70,
            'measured_at' => $when,
            'tag' => ReadingTag::None->value,
        ])
        ->assertRedirect(route('home'))
        ->assertInertiaFlash('toast.message', 'Blood pressure recorded.')
        ->assertInertiaFlash('toast.speech', 'Blood pressure 132 over 84. Pulse 70. Raised.');
});

it('flashes spoken numbers after saving a sugar reading', function () {
    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->post(route('glucose.store'), [
            'value' => '110',
            'context' => GlucoseContext::Fasting->value,
            'measured_at' => $when,
        ])
        ->assertRedirect(route('home'))
        ->assertInertiaFlash('toast.message', 'Glucose recorded.')
        ->assertInertiaFlash('toast.speech', 'Sugar 110 milligrams per deciliter. Fasting.');
});
