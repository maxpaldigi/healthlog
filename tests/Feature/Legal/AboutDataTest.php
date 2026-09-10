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

it('lists rxnorm as a medication name source', function () {
    expect(file_get_contents(resource_path('js/pages/legal/Privacy.vue')))
        ->toContain('RxNorm')
        ->and(file_get_contents(resource_path('js/pages/legal/AboutData.vue')))
        ->toContain('RxNorm')
        ->and(file_get_contents(base_path('docs/article-30-record.md')))
        ->toContain('RxNorm');
});

it('lists clinic reply links in privacy and the art 30 record', function () {
    expect(file_get_contents(resource_path('js/pages/legal/Privacy.vue')))
        ->toContain('reply link')
        ->and(file_get_contents(base_path('docs/article-30-record.md')))
        ->toContain('Clinic reply');
});
