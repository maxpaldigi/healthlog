<?php

it('sends clickjacking and content-type headers on public pages', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('does not noindex the public home page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag');
});
