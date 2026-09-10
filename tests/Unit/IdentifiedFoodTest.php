<?php

use App\Support\IdentifiedFood;

it('keeps a plain food name and a barcode', function () {
    $food = IdentifiedFood::fromArray([
        'label' => 'Apple',
        'barcode' => '3017620422003',
        'confidence' => 'high',
    ]);

    expect($food->label)->toBe('Apple')
        ->and($food->barcode)->toBe('3017620422003')
        ->and($food->confidence)->toBe('high')
        ->and($food->checkNote())->toBe('This looks like Apple. Check, then save.');
});

it('drops labels that look like health guidance', function () {
    $food = IdentifiedFood::fromArray([
        'label' => 'avoid this food',
        'barcode' => 'not-a-code',
        'confidence' => 'maybe',
    ]);

    expect($food->label)->toBe('')
        ->and($food->barcode)->toBeNull()
        ->and($food->confidence)->toBe('low')
        ->and($food->checkNote())->toBe("Couldn't tell what that food is. Search by name or type it.");
});

it('uses softer copy when confidence is low', function () {
    $food = IdentifiedFood::fromArray([
        'label' => 'roti',
        'barcode' => null,
        'confidence' => 'low',
    ]);

    expect($food->checkNote())->toBe('This might be roti. Check, then save.');
});
