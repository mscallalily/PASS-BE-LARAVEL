<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PassEmailVerificationNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return (new MailMessage)
            ->subject('Verify Your PASS Email Address')
            ->greeting('Hello!')
            ->line('Thank you for registering for the Parking Access and Space Management System (PASS).')
            ->line('Please verify your email address by clicking the button below.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('This verification link will expire in 60 minutes.')
            ->line($notifiable->isGuest()
                ? 'After verifying, sign in to submit a visitor parking request. A QR pass is only issued after that request is approved.'
                : 'Email verification does not mean your account is approved. Your account remains pending until reviewed by the PASS administrator.')
            ->line('If you did not register for PASS, you may ignore this email.');
    }
}