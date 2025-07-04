<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MailService;
use App\Models\User;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Carbon\Carbon;


class SendPendingTasksMail extends Command implements ShouldQueue
{

    use InteractsWithQueue;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendMail:pending-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily notification emails to users about their pending tasks';

    /**
     * Create a new command instance.
     *
     * @return void
     */


    protected $mailService;

    public function __construct(MailService $mailService)
    {
        parent::__construct();
        $this->mailService = $mailService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $pendingUserIds = Task::where('due_date', '<', Carbon::today())
            ->whereNotIn('status', ['completed', 'verified'])
            ->whereNotNull('assignee_id')
            ->distinct()
            ->pluck('assignee_id');

        if ($pendingUserIds->isEmpty()) {
            $this->info('No users with pending tasks.');
            return 0;
        }

        $users = User::whereIn('id', $pendingUserIds)
            ->with(['assigned_tasks' => function ($query) {
                $query->where('due_date', '<', Carbon::today())
                    ->whereNotIn('status', ['completed', 'verified']);
            }])
            ->get();

        foreach ($users as $user) {
            $tasks = $user->assigned_tasks;
            if ($tasks->isNotEmpty()) {
                $data = [
                    'user'  => $user,
                    'tasks' => $tasks,
                ];

                $this->mailService->sendMailAsync(
                    'emails.pendingTasks',
                    $data,
                    $user->email,
                    $user->name,
                    'Your Pending Tasks',
                    'no-reply@example.com',
                    'App Name'
                );
            }
        }

        $this->info('Pending tasks notification emails sent.');

    }
}
