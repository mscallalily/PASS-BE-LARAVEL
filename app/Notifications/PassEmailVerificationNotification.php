<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PassEmailVerificationNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function __construct(public readonly string $code)
    {
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your PASS Email Verification Code')
            ->greeting('Hello!')
            ->line('Enter this 6-digit code on the PASS Verify email page:')
            ->line($this->code)
            ->line('This code expires in 10 minutes. Requesting another code replaces this one.')
            ->line('Do not share this code with anyone.')
            ->line($notifiable->isGuest()
                ? 'After verification, sign in to submit your visitor request. A QR pass requires request approval.'
                : 'Email verification does not approve your account. Administrator approval is still required.')
            ->line('If you did not request this code, you can ignore this email.');
    }
}
