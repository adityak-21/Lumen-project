<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    // public function index()
    // {
    //     $totalUsers = User::count();
    //     $activeUsers = User::where('status', 'active')->count();
    //     $inactiveUsers = User::where('status', 'inactive')->count();

    //     return view('analytics.index', compact('totalUsers', 'activeUsers', 'inactiveUsers'));
    // }

    public function myTaskStatusStatistics()
    {
        try {
            $userId = Auth::user()->id;
            $assigned = $this->analyticsService->getTasksCountByStatus('assigned', $userId);
            $inProgress = $this->analyticsService->getTasksCountByStatus('in_progress', $userId);
            $completed = $this->analyticsService->getTasksCountByStatus('completed', $userId);
            $verified = $this->analyticsService->getTasksCountByStatus('verified', $userId);
            $overdue = $this->analyticsService->getOverdueTasksCount($userId);
            $due_today = $this->analyticsService->getTasksDueToday($userId);
            return response([
                'assigned' => $assigned,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'verified' => $verified,
                'overdue' => $overdue,
                'due_today' => $due_today,
            ]);
        } catch (\Exception $e) {
            return response(['error' => 'Failed to retrieve task statistics.'], 500);
        }

    }

    public function averageCompletionTime()
    {
        try {
            $userId = Auth::user()->id;
            $averageTime = $this->analyticsService->getAverageCompletionTime($userId);
            return response(['average_completion_time' => $averageTime]);
        }
        catch (\Exception $e) {
            return response(['error' => 'Failed to retrieve average completion time.'], 500);
        }
    }

    public function assignedVsCreated()
    {
        try{
            $userId = Auth::user()->id;
            return response($this->analyticsService->getAssignedVsCreated($userId));
        }
        catch (\Exception $e) {
            return response(['error' => 'Failed to retrieve assigned vs created tasks.'], 500);
        }
    }

    public function oldestOpenTasks()
    {
        $userId = Auth::user()->id;
        return response($this->analyticsService->getOldestOpenTasks($userId));
    }

}