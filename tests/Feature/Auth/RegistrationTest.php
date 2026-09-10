<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/Register')
                ->where('defaultTimezone', '')
                ->has('timezones')
                ->has('aliases'));
    }

    public function test_new_users_can_register_without_a_password(): void
    {
        $this->post(route('register.store'), registerAttributes())
            ->assertRedirect(route('login.check-email'));

        $this->assertGuest();
        $this->assertNotNull(User::query()->where('email', 'test@example.com')->first());
    }
}
