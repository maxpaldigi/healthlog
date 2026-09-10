<?php

use App\Support\NutritionFacts;

it('flags only published high marks and stays silent when unremarkable', function () {
    expect(NutritionFacts::sodiumFlag('180'))->toBeNull()
        ->and(NutritionFacts::sodiumFlag('599.99'))->toBeNull()
        ->and(NutritionFacts::sodiumFlag('600'))->toBe('600 mg sodium per 100 g (EU high-salt mark: 600 mg)')
        ->and(NutritionFacts::sugarsFlag('10'))->toBeNull()
        ->and(NutritionFacts::sugarsFlag('22.5'))->toBe('22.5 g sugars per 100 g (EU high-sugar mark: 22.5 g)');
});

it('cites the WHO salt reference as information', function () {
    expect(NutritionFacts::whoSaltReference())->toBe(
        'WHO reference: under 5 g salt (about 2000 mg sodium) a day.',
    );
});
