<?php
namespace App\Services;
use App\Models\Task;
use App\Models\User;

use Carbon\Carbon;

class AnalyticsService
{
    public function getTasksCountByStatus(string $status, $userId=null): int
    {
        if ($userId) {
            return Task::where('status', $status)
                        ->where('assignee_id', $userId)
                        ->count();
        }
        return Task::where('status', $status)->count();
    }

    public function getOverdueTasksCount($userId=null): int
    {
        if( $userId ) {
            return Task::where('due_date', '<', Carbon::today())
                        ->where('status', '!=', 'completed')
                        ->where('status', '!=', 'verified')
                        ->where('assignee_id', $userId)
                        ->count();
        }
        return Task::where('due_date', '<', Carbon::today())
                    ->where('status', '!=', 'completed')
                    ->count();
    }

    public function getTasksDueToday($userId=null): int
    {
        if( $userId ) {
            return Task::whereDate('due_date', Carbon::today())
                        ->where('assignee_id', $userId)
                        ->count();
        }
        return Task::whereDate('due_date', Carbon::today())->count();
    }

    public function getUsersCount(): int
    {
        return User::count();
    }
}