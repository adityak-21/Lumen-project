<?php
namespace App\Listeners;

use App\Events\TaskRegistered;
use App\Models\Task;
use App\Services\MailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTaskAssignedEmail implements ShouldQueue
{
    use InteractsWithQueue;

    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function handle(TaskRegistered $event)
    {
        $task = $event->task;
        $data = [
            'assignee' => $task->assignee->name,
            'title' => $task->title,
            'description' => $task->description,
            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d H:i:s') : null,
            'creator' => $task->creator->name,
            'status' => $task->status,

        ];
        $this->mailService->sendMailAsync('emails.taskAssigned', $data, $task->assignee->email, $task->assignee->name,
                                    'Task Assigned', 'no-reply@example.com', 'App Name');
    }
}