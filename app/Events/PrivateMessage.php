<?php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PrivateMessage implements ShouldBroadcast
{
    use InteractsWithSockets;

    public $message;
    public $userId;

    public function __construct($message, $userId)
    {
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }
        $this->message = $message;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return ['chat.' . $this->userId];
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}