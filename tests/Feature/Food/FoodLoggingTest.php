<?php

use App\Enums\FoodSource;
use App\Models\FoodLog;
use App\Models\OffProduct;
use App\Support\OpenFoodFacts;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

function fakeOffProduct(string $barcode = '3017620422003'): void
{
    Http::fake([
        'world.openfoodfacts.org/api/v2/product/*' => Http::response([
            'status' => 1,
            'product' => [
                'code' => $barcode,
                'product_name' => 'Roti',
                'brands' => 'Local Bakery',
                'nutriscore_grade' => 'c',
                'serving_quantity' => 80,
                'nutriments' => [
                    'sodium_100g' => 0.4,
                    'sugars_100g' => 2.1,
                    'carbohydrates_100g' => 46,
                    'salt_100g' => 1,
                ],
            ],
        ]),
        'world.openfoodfacts.org/cgi/search.pl*' => Http::response([
            'products' => [[
                'code' => $barcode,
                'product_name' => 'Roti',
                'brands' => 'Local Bakery',
                'nutriscore_grade' => 'c',
                'serving_quantity' => 80,
                'nutriments' => [
                    'sodium_100g' => 0.4,
                    'sugars_100g' => 2.1,
                    'carbohydrates_100g' => 46,
                ],
            ]],
        ]),
    ]);
}

it('shows the food page with worldwide search copy', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->get(route('food.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('food/LogFood')
            ->where('whoReference', 'WHO reference: under 5 g salt (about 2000 mg sodium) a day.')
            ->where('identified', null)
            ->has('portions', 5));
});

it('searches Open Food Facts worldwide and does not send an identifier', function () {
    Http::preventStrayRequests();
    fakeOffProduct();

    $user = userWithProfile(['email' => 'parent@example.com']);

    $this->actingAs($user)
        ->get(route('food.search', ['q' => 'roti']))
        ->assertOk()
        ->assertJsonPath('results.0.name', 'Roti')
        ->assertJsonPath('results.0.barcode', '3017620422003')
        ->assertJsonPath('results.0.attribution', 'Data from Open Food Facts (ODbL)');

    Http::assertSent(function ($request): bool {
        expect($request->url())->toContain('world.openfoodfacts.org')
            ->and($request->url())->not->toContain('us.openfoodfacts.org')
            ->and($request->url())->toContain('roti')
            ->and($request->header('User-Agent')[0] ?? '')->not->toContain('parent@example.com')
            ->and($request->body())->not->toContain('parent@example.com');

        return true;
    });
});

it('looks up a barcode once and serves the second request from cache', function () {
    Http::preventStrayRequests();
    fakeOffProduct('8901030865237');

    $user = userWithProfile();

    $this->actingAs($user)
        ->get(route('food.barcode', ['code' => '8901030865237']))
        ->assertOk()
        ->assertJsonPath('product.name', 'Roti');

    $this->actingAs($user)
        ->get(route('food.barcode', ['code' => '8901030865237']))
        ->assertOk()
        ->assertJsonPath('product.name', 'Roti');

    Http::assertSentCount(1);
    expect(OffProduct::query()->where('barcode', '8901030865237')->count())->toBe(1);
});

it('saves an Open Food Facts food and a custom name', function () {
    Http::preventStrayRequests();
    fakeOffProduct();

    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->subMinutes(20)->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->post(route('food.store'), [
            'source' => FoodSource::OpenFoodFacts->value,
            'source_ref' => '3017620422003',
            'portion_g' => '80',
            'portion_label' => 'One serving',
            'eaten_at' => $when,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('food.create'));

    $fromOff = FoodLog::query()->first();

    expect($fromOff)->not->toBeNull()
        ->and($fromOff->profile_id)->toBe($user->defaultProfile->id)
        ->and($fromOff->source)->toBe(FoodSource::OpenFoodFacts)
        ->and($fromOff->label)->toBe('Roti')
        ->and((string) $fromOff->sodium_mg_100g)->toBe('400.00');

    $this->actingAs($user)
        ->post(route('food.store'), [
            'source' => FoodSource::Custom->value,
            'label' => 'Homemade dal',
            'portion_g' => '200',
            'portion_label' => 'A bowl',
            'eaten_at' => $when,
        ])
        ->assertSessionHasNoErrors();

    expect(FoodLog::query()->where('source', FoodSource::Custom)->first()?->label)->toBe('Homemade dal');
});

it('lets a caregiver log food and forbids a stranger', function () {
    Http::preventStrayRequests();
    fakeOffProduct();

    $owner = userWithProfile();
    $profile = $owner->defaultProfile;
    $caregiver = caregiverFor($profile);
    $stranger = userWithProfile();
    $when = $profile->nowInTimezone()->subHour()->format('Y-m-d\TH:i');

    $this->actingAs($caregiver)
        ->withSession(['current_profile_id' => $profile->id])
        ->post(route('food.store'), [
            'source' => FoodSource::Custom->value,
            'label' => 'Rice',
            'portion_g' => '100',
            'portion_label' => 'One serving',
            'eaten_at' => $when,
        ])
        ->assertRedirect(route('food.create'));

    $log = FoodLog::query()->where('profile_id', $profile->id)->first();

    expect($log?->profile_id)->toBe($profile->id)
        ->and($stranger->can('delete', $log))->toBeFalse()
        ->and($caregiver->can('delete', $log))->toBeTrue();

    $this->actingAs($stranger)
        ->delete(route('food.destroy', $log))
        ->assertForbidden();
});

it('converts sodium grams from Open Food Facts into milligrams', function () {
    Http::preventStrayRequests();
    fakeOffProduct();

    $food = app(OpenFoodFacts::class)->byBarcode('3017620422003');

    expect($food?->sodiumMg100g)->toBe('400.00')
        ->and($food?->name)->toBe('Roti');
});
