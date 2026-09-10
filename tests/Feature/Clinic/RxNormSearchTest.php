<?php

use App\Support\RxNorm;
use Illuminate\Support\Facades\Http;

function fakeRxNorm(string $rxcui = '6809', string $name = 'metformin'): void
{
    Http::fake([
        'rxnav.nlm.nih.gov/REST/approximateTerm.json*' => Http::response([
            'approximateGroup' => [
                'candidate' => [
                    ['rxcui' => $rxcui, 'score' => '100', 'rank' => '1'],
                ],
            ],
        ]),
        'rxnav.nlm.nih.gov/REST/rxcui/*/properties.json' => Http::response([
            'properties' => [
                'rxcui' => $rxcui,
                'name' => $name,
                'tty' => 'IN',
            ],
        ]),
    ]);
}

it('searches rxnorm by the typed words and does not send an identifier', function () {
    Http::preventStrayRequests();
    fakeRxNorm();

    $user = userWithProfile(['email' => 'parent@example.com', 'name' => 'Meena']);

    $this->actingAs($user)
        ->get(route('medications.search', ['q' => 'metformin']))
        ->assertOk()
        ->assertJsonPath('results.0.rxcui', '6809')
        ->assertJsonPath('results.0.name', 'metformin')
        ->assertJsonPath('attribution', RxNorm::ATTRIBUTION);

    Http::assertSent(function ($request) use ($user): bool {
        expect($request->url())->toContain('rxnav.nlm.nih.gov')
            ->and($request->url())->not->toContain($user->email)
            ->and($request->url())->not->toContain('Meena')
            ->and($request->header('User-Agent')[0] ?? '')->not->toContain($user->email)
            ->and($request->body())->not->toContain($user->email);

        return true;
    });
});

it('saves a custom medication name without calling rxnorm', function () {
    Http::preventStrayRequests();

    $user = userWithProfile();

    $this->actingAs($user)
        ->post(route('medications.store'), [
            'name' => 'Amlodipine',
            'dose' => '5 mg',
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('clinic.index'));

    expect($user->defaultProfile->medications()->first()->name)->toBe('Amlodipine')
        ->and($user->defaultProfile->medications()->first()->rxcui)->toBeNull();

    Http::assertNothingSent();
});

it('stores the official rxnorm name when a code is picked', function () {
    Http::preventStrayRequests();
    fakeRxNorm('6809', 'metformin');

    $user = userWithProfile();

    $this->actingAs($user)
        ->post(route('medications.store'), [
            'name' => 'Metformin 500',
            'rxcui' => '6809',
            'dose' => '500 mg',
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect($user->defaultProfile->medications()->first()->name)->toBe('metformin')
        ->and($user->defaultProfile->medications()->first()->rxcui)->toBe('6809');
});
