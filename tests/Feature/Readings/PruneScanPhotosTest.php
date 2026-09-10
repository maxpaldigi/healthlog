<?php

use App\Contracts\ExtractsDeviceReading;
use App\Exceptions\CouldNotReadPhotoException;
use App\Support\ExtractedReading;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

function scanExtract(): ExtractedReading
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
    ]);
}

it('deletes the photo after a successful extract', function () {
    Storage::fake('local');
    $user = userWithProfile();

    $this->mock(ExtractsDeviceReading::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andReturn(scanExtract());
    });

    $this->actingAs($user)
        ->post(route('scan.store'), [
            'photo' => UploadedFile::fake()->image('device.jpg', 200, 200),
        ])
        ->assertRedirect(route('bp.create'));

    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('deletes the photo when the extract fails', function () {
    Storage::fake('local');
    $user = userWithProfile();

    $this->mock(ExtractsDeviceReading::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andThrow(CouldNotReadPhotoException::make());
    });

    $this->actingAs($user)
        ->from(route('scan'))
        ->post(route('scan.store'), [
            'photo' => UploadedFile::fake()->image('device.jpg', 200, 200),
        ])
        ->assertRedirect(route('scan'));

    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('prunes leftover scan photos older than one hour', function () {
    $disk = Storage::disk('local');
    $disk->put('scans/old.jpg', 'old');
    $disk->put('scans/new.jpg', 'new');
    touch($disk->path('scans/old.jpg'), now()->subHours(2)->getTimestamp());

    $this->artisan('healthlog:prune-scans')->assertSuccessful();

    expect($disk->exists('scans/old.jpg'))->toBeFalse()
        ->and($disk->exists('scans/new.jpg'))->toBeTrue();

    $disk->delete('scans/new.jpg');
});
