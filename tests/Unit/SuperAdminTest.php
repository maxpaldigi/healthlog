<?php

use App\Support\SuperAdmin;

it('uses the configured operator email', function () {
    config(['auth.super_admin_email' => 'maxpal1234@gmail.com']);

    expect(SuperAdmin::email())->toBe('maxpal1234@gmail.com')
        ->and(SuperAdmin::matches('maxpal1234@gmail.com'))->toBeTrue()
        ->and(SuperAdmin::matches('MaxPal1234@gmail.com'))->toBeTrue()
        ->and(SuperAdmin::matches('someone@example.com'))->toBeFalse();
});
