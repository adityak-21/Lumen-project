<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class MailService
{
    public function sendMail(
        string $bladeView,
        array $data,
        string $toEmail,
        ?string $toName,
        string $subject,
        string $fromEmail = 'no-reply@example.com',
        ?string $fromName = 'App Name'
    ): void {
        Mail::send($bladeView, $data, function ($message) use (
            $toEmail, $toName, $subject, $fromEmail, $fromName
        ) {
            $message->to($toEmail, $toName)
                    ->subject($subject)
                    ->from($fromEmail, $fromName);
        });
    }
}