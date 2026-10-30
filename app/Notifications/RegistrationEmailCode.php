<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationEmailCode extends Notification
{
    public function __construct(public readonly string $code) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Verify your DYCI email for PASS')
            ->greeting('Welcome to PASS')
            ->line('Enter this code in the registration you started:')
            ->line($this->code)
            ->line('This code expires in 10 minutes. Never share it with anyone.')
            ->line('After verifying your email, photograph your driver\'s license to complete the automatic registration checks.')
            ->line('If you did not start this registration, ignore this email. No account has been activated.');
    }
}
