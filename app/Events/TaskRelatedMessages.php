<?php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TaskRelatedMessages implements ShouldBroadcast
{
    use InteractsWithSockets;

    public $message;
    public $userId;

    public function __construct($title, $message, $userId, $notificationId)
    {
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }
        if (!$notificationId) {
            throw new \InvalidArgumentException('Notification ID is required');
        }
        $this->title = $title;
        $this->message = $message;
        $this->userId = $userId;
        $this->notificationId = $notificationId;
    }

    public function broadcastOn()
    {
        return ['task.' . $this->userId];
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}