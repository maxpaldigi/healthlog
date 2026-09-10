<?php

use Inertia\Testing\AssertableInertia as Assert;

it('shows the data sources page to guests', function () {
    $this->get(route('about.data'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('legal/AboutData'));
});

it('shows the privacy notice to guests', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('legal/Privacy'));
});
