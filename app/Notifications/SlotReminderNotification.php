<?php

namespace App\Notifications;

use App\Enums\BpDaySlot;
use App\Models\Profile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlotReminderNotification extends Notification
{
    public function __construct(public Profile $profile, public BpDaySlot $slot) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $slot = strtolower($this->slot->label());

        return (new MailMessage)
            ->subject('Time to log '.$slot.' blood pressure')
            ->greeting('Hello '.$this->profile->name)
            ->line('This is a reminder to log the '.$slot.' blood pressure reading for '.$this->profile->name.'.')
            ->action('Add blood pressure', route('bp.create'))
            ->line('Need to add blood sugar too? Open '.route('glucose.create'))
            ->line('This is a record-keeping reminder, not medical advice.');
    }
}
