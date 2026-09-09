<?php

use App\Contracts\ExtractsDeviceReading;
use App\Enums\GlucoseContext;
use App\Enums\GlucoseUnit;
use App\Enums\ReadingSource;
use App\Enums\ReadingTag;
use App\Exceptions\CouldNotReadPhotoException;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Support\ExtractedReading;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

function scanPhoto(): UploadedFile
{
    return UploadedFile::fake()->image('device.jpg', 200, 200);
}

function extractedReading(array $overrides = []): ExtractedReading
{
    return ExtractedReading::fromArray([
        'device_type' => 'blood_pressure_monitor',
        'systolic' => 132,
        'diastolic' => 84,
        'pulse' => 72,
        'glucose' => null,
        'glucose_unit_on_screen' => null,
        'confidence' => 'high',
        'notes' => 'Omron LCD',
        ...$overrides,
    ]);
}

it('does not save a reading until the user confirms extracted numbers', function () {
    Storage::fake('local');
    $user = userWithProfile();

    $this->mock(ExtractsDeviceReading::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andReturn(extractedReading());
    });

    $this->actingAs($user)
        ->post(route('scan.store'), ['photo' => scanPhoto()])
        ->assertRedirect(route('bp.create'));

    expect(BpReading::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);

    $this->actingAs($user)
        ->get(route('bp.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/LogBp')
            ->where('source', 'photo')
            ->where('defaults.systolic', 132)
            ->where('defaults.diastolic', 84)
            ->where('defaults.pulse', 72));
});

it('saves a confirmed photo blood pressure reading as photo source', function () {
    Storage::fake('local');
    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->format('Y-m-d\TH:i');

    $this->mock(ExtractsDeviceReading::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andReturn(extractedReading());
    });

    $this->actingAs($user)
        ->post(route('scan.store'), ['photo' => scanPhoto()])
        ->assertRedirect(route('bp.create'));

    $this->actingAs($user)
        ->post(route('bp.store'), [
            'systolic' => 132,
            'diastolic' => 84,
            'pulse' => 72,
            'measured_at' => $when,
            'tag' => ReadingTag::None->value,
            'source' => ReadingSource::Photo->value,
        ])
        ->assertRedirect(route('home'));

    $reading = BpReading::query()->first();

    expect($reading)->not->toBeNull()
        ->and($reading->source)->toBe(ReadingSource::Photo)
        ->and($reading->systolic)->toBe(132);
});

it('prefills glucose and shows both units when the screen unit differs', function () {
    Storage::fake('local');
    $user = userWithProfile([], ['glucose_unit' => GlucoseUnit::MgDl]);

    $this->mock(ExtractsDeviceReading::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andReturn(extractedReading([
            'device_type' => 'glucometer',
            'systolic' => null,
            'diastolic' => null,
            'pulse' => null,
            'glucose' => 6.2,
            'glucose_unit_on_screen' => 'mmol/L',
            'confidence' => 'high',
            'notes' => 'Accu-Chek',
        ]));
    });

    $this->actingAs($user)
        ->post(route('scan.store'), ['photo' => scanPhoto()])
        ->assertRedirect(route('glucose.create'));

    $this->actingAs($user)
        ->get(route('glucose.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/LogSugar')
            ->where('source', 'photo')
            ->where('extractionNote', fn (?string $note): bool => is_string($note) && str_contains($note, '6.2 mmol/L')));
});

it('asks the user to retake when confidence is low and deletes the image', function () {
    Storage::fake('local');
    $user = userWithProfile();

    $this->mock(ExtractsDeviceReading::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andReturn(extractedReading([
            'device_type' => 'unknown',
            'systolic' => null,
            'diastolic' => null,
            'confidence' => 'low',
            'notes' => 'Blurred',
        ]));
    });

    $this->actingAs($user)
        ->from(route('scan'))
        ->post(route('scan.store'), ['photo' => scanPhoto()])
        ->assertRedirect(route('scan'));

    $this->actingAs($user)
        ->get(route('scan'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/Scan')
            ->where('status', fn (?string $status): bool => is_string($status) && str_contains($status, 'retake')));

    expect(BpReading::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('falls back to manual entry when the API fails and deletes the image', function () {
    Storage::fake('local');
    $user = userWithProfile();

    $this->mock(ExtractsDeviceReading::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andThrow(CouldNotReadPhotoException::make());
    });

    $this->actingAs($user)
        ->from(route('scan'))
        ->post(route('scan.store'), ['photo' => scanPhoto()])
        ->assertRedirect(route('scan'));

    $this->actingAs($user)
        ->get(route('scan'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', "Couldn't read that photo, please enter the numbers manually"));

    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('rejects a photo larger than 10 MB', function () {
    Storage::fake('local');
    $user = userWithProfile();

    $this->actingAs($user)
        ->from(route('scan'))
        ->post(route('scan.store'), [
            'photo' => UploadedFile::fake()->image('huge.jpg')->size(10241),
        ])
        ->assertRedirect(route('scan'))
        ->assertSessionHasErrors('photo');
});

it('opens a sugar-meter scan screen and returns to log sugar if the photo cannot be read', function () {
    Storage::fake('local');
    $user = userWithProfile();

    $this->actingAs($user)
        ->get(route('scan', ['expect' => 'glucose']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/Scan')
            ->where('expect', 'glucose'));

    $this->mock(ExtractsDeviceReading::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andThrow(CouldNotReadPhotoException::make());
    });

    $this->actingAs($user)
        ->from(route('scan', ['expect' => 'glucose']))
        ->post(route('scan.store'), [
            'photo' => scanPhoto(),
            'expect' => 'glucose',
        ])
        ->assertRedirect(route('glucose.create'));

    $this->actingAs($user)
        ->get(route('glucose.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/LogSugar')
            ->where('scanStatus', "Couldn't read that photo, please enter the numbers manually"));
});

it('records confirmed photo glucose without auto-saving first', function () {
    Storage::fake('local');
    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->format('Y-m-d\TH:i');

    $this->mock(ExtractsDeviceReading::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andReturn(extractedReading([
            'device_type' => 'glucometer',
            'systolic' => null,
            'diastolic' => null,
            'pulse' => null,
            'glucose' => 118,
            'glucose_unit_on_screen' => 'mg/dL',
        ]));
    });

    $this->actingAs($user)
        ->post(route('scan.store'), ['photo' => scanPhoto()])
        ->assertRedirect(route('glucose.create'));

    expect(GlucoseReading::query()->count())->toBe(0);

    $this->actingAs($user)
        ->post(route('glucose.store'), [
            'value' => '118',
            'context' => GlucoseContext::Random->value,
            'measured_at' => $when,
            'source' => ReadingSource::Photo->value,
        ])
        ->assertRedirect(route('home'));

    expect(GlucoseReading::query()->first()?->source)->toBe(ReadingSource::Photo);
});
