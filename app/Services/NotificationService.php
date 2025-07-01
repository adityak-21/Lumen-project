<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * Create a new notification.
     *
     * @param string $title
     * @param string $description
     * @return Notification
     */
    public function createNotification(int $userId, string $title, string $description): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'is_read' => false,
        ]);
    }
}