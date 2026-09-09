<?php

use App\Enums\BpDaySlot;
use App\Models\BpReading;
use App\Notifications\SlotReminderNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

it('emails a due empty slot in the profile timezone', function () {
    Notification::fake();

    $this->travelTo(Carbon::parse('2026-09-08 08:05:00', 'Asia/Kolkata'));

    $user = userWithProfile([], [
        'timezone' => 'Asia/Kolkata',
        'reminders_enabled' => true,
        'reminder_morning' => '08:00',
    ]);

    $this->artisan('healthlog:send-reminders')->assertSuccessful();

    Notification::assertSentTo($user, SlotReminderNotification::class);

    $this->artisan('healthlog:send-reminders')->assertSuccessful();

    Notification::assertSentToTimes($user, SlotReminderNotification::class, 1);

    $this->travelBack();
});

it('does not email when the slot is already logged', function () {
    Notification::fake();

    $this->travelTo(Carbon::parse('2026-09-08 08:05:00', 'Asia/Kolkata'));

    $user = userWithProfile([], [
        'timezone' => 'Asia/Kolkata',
        'reminders_enabled' => true,
        'reminder_morning' => '08:00',
    ]);

    BpReading::factory()->for($user->defaultProfile)->create([
        'measured_at' => Carbon::parse('2026-09-08 07:40:00', 'Asia/Kolkata')->utc(),
    ]);

    $this->artisan('healthlog:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();

    $this->travelBack();
});

it('shows a due reminder on home after the reminder time', function () {
    $this->travelTo(Carbon::parse('2026-09-08 14:10:00', 'Asia/Kolkata'));

    $user = userWithProfile([], [
        'timezone' => 'Asia/Kolkata',
        'reminders_enabled' => true,
        'reminder_afternoon' => '14:00',
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('reminders.enabled', true)
            ->where('reminders.due', fn ($due): bool => collect($due)->contains('key', BpDaySlot::Afternoon->value)));

    $this->travelBack();
});
