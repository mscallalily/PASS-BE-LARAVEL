<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Deliberately synchronous: credentials must not be serialized into queue jobs. */
class PrivilegedAccountInvitation extends Notification
{
    public function __construct(
        public readonly ?string $temporaryPassword,
        public readonly string $code,
        public readonly string $loginUrl,
    ) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->subject($this->temporaryPassword ? 'Activate your PASS staff account' : 'Your PASS activation code')
            ->greeting('Hello!')
            ->line('A PASS administrator has invited you to a staff account.')
            ->line('Sign-in email: '.$notifiable->email);
        if ($this->temporaryPassword !== null) {
            $message->line('Temporary password: '.$this->temporaryPassword)
                ->line('The temporary password expires in 24 hours. A resent invitation replaces it.');
        }
        return $message->line('Activation code: '.$this->code)
            ->line('The code expires in 10 minutes and can be used once. You can request another code after signing in.')
            ->action('Set up my PASS account', $this->loginUrl)
            ->line('Sign in, verify the code, then choose a different password. Dashboard access stays blocked until setup is complete.')
            ->line('Do not forward this email or share its credentials. Contact the PASS office if you were not expecting this invitation.');
    }
}
