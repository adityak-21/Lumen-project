<?php
namespace App\Services;
use App\Models\Task;
use App\Models\User;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


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

     public function getAverageCompletionTime($userId)
    {
        $tasks = Task::select(DB::raw("DATE_FORMAT(updated_at, '%Y-%m') as month"),
                    DB::raw("AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at))/86400 as avg_days"))
                ->where('assignee_id', $userId)
                ->whereIn('status', ['completed', 'verified'])
                ->groupBy('month')
                ->orderBy('month')
                ->get();

        $result = [];
        foreach ($tasks as $row) {
            $result[$row->month] = round($row->avg_days, 1);
        }
        return $result;
    }

    public function getAssignedVsCreated($userId)
    {
        $assigned = Task::where('assignee_id', $userId)->count();
        $created = Task::where('created_by', $userId)->count();
        return [
            'assigned_to_me' => $assigned,
            'created_by_me' => $created
        ];
    }

    public function getOldestOpenTasks($userId)
    {
        $openTasks = Task::where('assignee_id', $userId)
            ->whereNotIn('status', ['completed', 'verified'])
            ->orderBy('created_at')
            ->limit(5)
            ->get(['id', 'title', 'created_at', 'status']);

        $result = [];
        foreach ($openTasks as $task) {
            $result[] = [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'created_at' => $task->created_at,
                'days_open' => Carbon::now()->diffInDays($task->created_at),
            ];
        }
        return $result;
    }
}