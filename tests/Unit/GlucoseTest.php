<?php

use App\Enums\GlucoseUnit;
use App\Support\Glucose;

it('stores mmol/l values as mg/dl and converts back for display', function () {
    $stored = Glucose::toMgDl('6.00', GlucoseUnit::MmolL);

    expect($stored)->toBe('108.11')
        ->and(Glucose::fromMgDl($stored, GlucoseUnit::MmolL))->toBe('6.00')
        ->and(Glucose::toMgDl('108.11', GlucoseUnit::MgDl))->toBe('108.11')
        ->and(Glucose::fromMgDl('108.11', GlucoseUnit::MgDl))->toBe('108.11');
});
