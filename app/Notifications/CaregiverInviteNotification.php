<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaregiverInviteNotification extends Notification
{
    public function __construct(
        public string $url,
        public string $profileName,
        public string $inviterName,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->inviterName.' invited you to help with a HealthyMe log')
            ->line($this->inviterName.' invited you to view and add readings for '.$this->profileName.'.')
            ->line($this->profileName.' will be able to see that you have access, and can remove it at any time.')
            ->action('Accept invite', $this->url)
            ->line('This link expires in 7 days. If you were not expecting this, you can ignore this email.');
    }
}
