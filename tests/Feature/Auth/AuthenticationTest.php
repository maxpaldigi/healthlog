<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_users_can_request_a_magic_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
        ])->assertRedirect(route('login.check-email'));

        $this->assertGuest();
        Notification::assertSentTo($user, MagicLinkNotification::class);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
