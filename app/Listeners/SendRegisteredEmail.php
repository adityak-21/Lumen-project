<?php
namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\MailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendRegisteredEmail implements ShouldQueue
{
    use InteractsWithQueue;

    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function handle(UserRegistered $event)
    {
        $user = $event->user;
        $confirmationUrl = env('FRONTEND_URL', 'http://localhost:3000') . "/confirmEmail/{$user->confirmation_token}";
        // $confirmationUrl = url("/api/confirm/{$user->confirmation_token}");
        $data = [
            'name' => $user->name,
            'confirmationUrl' => $confirmationUrl,
        ];
        $this->mailService->sendMailAsync('emails.confirmation', $data, $user->email, $user->name,
                                    'Confirm your email', 'no-reply@example.com', 'App Name');
    }
}