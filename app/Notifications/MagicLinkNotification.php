<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification
{
    public function __construct(public string $url) {}

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
            ->subject('Your HealthyMe login link')
            ->line('Tap the button below to log in. This link expires in 15 minutes and can only be used once.')
            ->action('Log in to HealthyMe', $this->url)
            ->line('If you did not request this, you can ignore this email.');
    }
}
