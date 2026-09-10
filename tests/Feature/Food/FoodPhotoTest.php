<?php

use App\Contracts\ExtractsFoodFromPhoto;
use App\Exceptions\CouldNotReadPhotoException;
use App\Support\IdentifiedFood;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

it('names food from a photo, searches worldwide, and deletes the photo', function () {
    config(['services.gemini.key' => 'test-gemini-key']);
    Storage::fake('local');
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'label' => 'Apple',
                            'barcode' => null,
                            'confidence' => 'high',
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ],
            ]],
        ]),
        'world.openfoodfacts.org/cgi/search.pl*' => Http::response([
            'products' => [[
                'code' => '1234567890123',
                'product_name' => 'Fresh apples',
                'brands' => '',
                'nutriments' => [
                    'sugars_100g' => 10.4,
                    'carbohydrates_100g' => 14,
                ],
            ]],
        ]),
    ]);

    $user = userWithProfile(['email' => 'parent@example.com']);

    $this->actingAs($user)
        ->followingRedirects()
        ->post(route('food.photo'), [
            'photo' => UploadedFile::fake()->image('apple.jpg', 80, 80),
        ])
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('food/LogFood')
            ->where('identified.label', 'Apple')
            ->where('identified.note', 'This looks like Apple. Check, then save.')
            ->where('identified.results.0.name', 'Fresh apples'));

    expect(Storage::disk('local')->allFiles())->toBe([]);

    Http::assertSent(function ($request): bool {
        if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
            return false;
        }

        expect($request->body())->not->toContain('parent@example.com');

        return true;
    });

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), 'world.openfoodfacts.org')
            && str_contains(strtolower($request->url()), 'apple')
            && ! str_contains($request->url(), 'parent@example.com');
    });
});

it('deletes the food photo after a successful identify', function () {
    Storage::fake('local');
    Http::fake([
        'world.openfoodfacts.org/*' => Http::response(['products' => []]),
    ]);

    $this->mock(ExtractsFoodFromPhoto::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andReturn(new IdentifiedFood('Apple', null, 'high'));
    });

    $this->actingAs(userWithProfile())
        ->post(route('food.photo'), [
            'photo' => UploadedFile::fake()->image('apple.jpg', 80, 80),
        ])
        ->assertRedirect(route('food.create'));

    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('deletes the food photo when identify fails', function () {
    Storage::fake('local');

    $this->mock(ExtractsFoodFromPhoto::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andThrow(
            CouldNotReadPhotoException::make("Couldn't tell what that food is. Search by name or type it."),
        );
    });

    $this->actingAs(userWithProfile())
        ->from(route('food.create'))
        ->post(route('food.photo'), [
            'photo' => UploadedFile::fake()->image('apple.jpg', 80, 80),
        ])
        ->assertRedirect(route('food.create'))
        ->assertSessionHas('scan_status', "Couldn't tell what that food is. Search by name or type it.");

    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('asks for a Gemini key when food photo reading is not set up', function () {
    config(['services.gemini.key' => null]);
    putenv('GEMINI_API_KEY');
    unset($_ENV['GEMINI_API_KEY'], $_SERVER['GEMINI_API_KEY']);
    Storage::fake('local');

    $this->actingAs(userWithProfile())
        ->post(route('food.photo'), [
            'photo' => UploadedFile::fake()->image('apple.jpg', 80, 80),
        ])
        ->assertRedirect(route('food.create'))
        ->assertSessionHas('scan_status', 'Photo reading is not set up yet. Search by name or type it.');

    expect(Storage::disk('local')->allFiles())->toBe([]);
});
