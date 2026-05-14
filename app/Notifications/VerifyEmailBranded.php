<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class VerifyEmailBranded extends VerifyEmail
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject(Lang::get('Verify Your FluxMedia Account'))
            ->view('emails.verify-email', [
                'url' => $verificationUrl,
                'logoUrl' => asset('assets/images/icon_only.webp'),
            ]);
    }
}
