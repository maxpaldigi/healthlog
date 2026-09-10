<?php

use App\Enums\GlucoseContext;
use App\Enums\ReadingTag;
use App\Models\BpReading;
use App\Models\GlucoseReading;

it('stores one blood pressure row when the same client uuid is posted twice', function () {
    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->subMinutes(10)->format('Y-m-d\TH:i');
    $uuid = '11111111-1111-4111-8111-111111111111';

    $payload = [
        'systolic' => 132,
        'diastolic' => 84,
        'pulse' => 70,
        'measured_at' => $when,
        'tag' => ReadingTag::None->value,
        'client_uuid' => $uuid,
    ];

    $this->actingAs($user)
        ->post(route('bp.store'), $payload)
        ->assertRedirect(route('home'));

    $this->actingAs($user)
        ->post(route('bp.store'), [
            ...$payload,
            'systolic' => 128,
            'diastolic' => 80,
        ])
        ->assertRedirect(route('home'));

    expect(BpReading::query()->count())->toBe(1)
        ->and(BpReading::query()->first())
        ->systolic->toBe(128)
        ->diastolic->toBe(80)
        ->client_uuid->toBe($uuid);
});

it('stores one glucose row when the same client uuid is posted twice', function () {
    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->format('Y-m-d\TH:i');
    $uuid = '22222222-2222-4222-8222-222222222222';

    $this->actingAs($user)
        ->post(route('glucose.store'), [
            'value' => '110',
            'context' => GlucoseContext::Random->value,
            'measured_at' => $when,
            'client_uuid' => $uuid,
        ])
        ->assertRedirect(route('home'));

    $this->actingAs($user)
        ->post(route('glucose.store'), [
            'value' => '118',
            'context' => GlucoseContext::Fasting->value,
            'measured_at' => $when,
            'client_uuid' => $uuid,
        ])
        ->assertRedirect(route('home'));

    expect(GlucoseReading::query()->count())->toBe(1)
        ->and((string) GlucoseReading::query()->first()->value_mg_dl)->toBe('118.00')
        ->and(GlucoseReading::query()->first()->client_uuid)->toBe($uuid);
});
