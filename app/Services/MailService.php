<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;
use Carbon\Carbon;

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
    public function sendMailAsync(
        string $bladeView,
        array $data,
        string $toEmail,
        ?string $toName,
        string $subject,
        string $fromEmail = 'no-reply@example.com',
        ?string $fromName = 'App Name'
    ): void {
        $mailable = new GenericMail($bladeView, $data, $subject, $fromEmail, $fromName);

        // \Log::info('Sending mail async', [
        //     'toEmail' => $toEmail,
        //     'toName' => $toName,
        //     'subject' => $subject,
        //     'fromEmail' => $fromEmail,
        //     'fromName' => $fromName,
        // ]);

        if ($toName) {
            Mail::to($toEmail, $toName)->queue($mailable);

        } else {
            Mail::to($toEmail)->queue($mailable);

        }
    }
}