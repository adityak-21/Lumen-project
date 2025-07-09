<?php
namespace App\Services;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;


class TaskService
{
    // interface for creating a task
    // defing params 
    // create getter and setter methods

    public function createTask($title, $assignee_id, $userId, array $data)
    {
        $task = Task::create([
            'title' => $title,
            'description' => $data['description'] ?? null,
            'assignee_id' => $assignee_id,
            'due_date' => isset($data['due_date']) ? Carbon::parse($data['due_date'])->toDateTimeString() : null,
            'created_by' => $userId,
            'status' => 'assigned',
        ]);
        $task->save();
        return $task;
    }

    public function updateTaskTitle($taskId, $newtitle)
    {
        $task = Task::find($taskId);
        if(!$task) {
            throw new \Exception('Task not found');
        }
        if(Auth::user()->id !== $task->created_by) {
            throw new \Exception('You are not authorized to update this task');
        }
        $task->title = $newtitle;
        $task->save();
        return $task;
    }

    public function updateTaskDescription($taskId, $newDescription)
    {
        $task = Task::find($taskId);
        if(!$task) {
            throw new \Exception('Task not found');
        }
        if(Auth::user()->id !== $task->created_by) {
            throw new \Exception('You are not authorized to update this task');
        }
        $task->description = $newDescription;
        $task->save();
        return $task;
    }

    public function updateTaskDueDate($taskId, $newDueDate)
    {
        $task = Task::find($taskId);
        if(!$task) {
            throw new \Exception('Task not found');
        }
        if(Auth::user()->id !== $task->created_by) {
            throw new \Exception('You are not authorized to update this task');
        }
        $task->due_date = isset($newDueDate) ? Carbon::parse($newDueDate)->toDateTimeString() : null;
        $task->save();
        return $task;
    }

    public function updateTaskStatus($taskId, $newStatus)
    {
        $task = Task::find($taskId);
        if(!$task) {
            throw new \Exception('Task not found');
        }
        if($newStatus==='verified' && Auth::user()->id !== $task->created_by) {
            throw new \Exception('You are not authorized to update this task');
        }
        if(Auth::user()->id !== $task->created_by && Auth::user()->id !== $task->assignee_id) {
            throw new \Exception('You are not authorized to update this task');
        }
        if(!in_array($newStatus, ['assigned', 'in_progress', 'completed', 'verified'])) {
            throw new \Exception('Invalid status');
        }
        $task->status = $newStatus;
        $task->save();
        return $task;
    }

    public function deleteTask($taskId)
    {
        $task = Task::find($taskId);
        if(!$task) {
            throw new \Exception('Task not found');
        }
        if(!Gate::allows('is-Admin')) {
            throw new \Exception('You are not authorized to delete this task');
        }
        $task->delete();
        return ['status' => 'success', 'message' => 'Task deleted successfully.'];
    }

    public function listMyTasks($userId, array $filters = [], $pageNumber = 1, $perPage = 10)
    {
        $query = Task::query();

        $query = Task::where('assignee_id', $userId);

        if (!empty($filters['title'])) {
            $query->where('title', 'like', $filters['title'] . '%');
        }

        if (!empty($filters['from'])) {
            $query->where('due_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('due_date', '<=', $filters['to']);
        }

        if (!empty($filters['created_by'])) {
            $query->whereHas('creator', function ($q) use ($filters) {
                $q->where('name', 'like', $filters['created_by'] . '%');
            });
            $query->with(['creator' => function ($q) use ($filters) {
                $q->where('name', 'like', $filters['created_by'] . '%');
            }]);
        }
        else $query->with('creator');

        if( !empty($filters['status'])) {
            $query->where('status', 'like', $filters['status']);
        }

        $sortableFields = ['title', 'due_date', 'status'];
        if (!empty($filters['sort_by']) && in_array($filters['sort_by'], $sortableFields)) {
            $sortDirection = !empty($filters['sort_order']) && in_array($filters['sort_order'], ['asc', 'desc']) ? $filters['sort_order'] : 'asc';
            $query->orderBy($filters['sort_by'], $sortDirection);
        } else {
            $query->orderBy('due_date', 'asc');
        }

        $totalCount = $query->count();

        $query->limit($perPage)->offset(($pageNumber-1) * $perPage);
        $tasks = $query->get();
        $count = $tasks->count();
        $result = [];
        foreach ($tasks as $activity) {
            $result[] = [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description ? $activity->description : null,
                'due_date' => $activity->due_date ? Carbon::parse($activity->due_date)->toDateTimeString(): null,
                'status' => $activity->status,
                'created_by' => $activity->creator ? $activity->creator->name : null,
                'created_at' => Carbon::parse($activity->created_at)->toDateTimeString(),
                'updated_at' => Carbon::parse($activity->updated_at)->toDateTimeString(),
                'total_count' => $totalCount,
            ];
        }
        
        return $result;
    }

    public function listAllTasks($userId, array $filters = [], $pageNumber = 1, $perPage = 10)
    {
        $query = Task::query();

        if (!empty($filters['title'])) {
            $query->where('title', 'like', $filters['title'] . '%');
        }

        if (!empty($filters['from'])) {
            $query->where('due_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('due_date', '<=', $filters['to']);
        }

        if (!empty($filters['created_by'])) {
            $query->whereHas('creator', function ($q) use ($filters) {
                $q->where('name', 'like', $filters['created_by'] . '%');
            });
            $query->with(['creator' => function ($q) use ($filters) {
                $q->where('name', 'like', $filters['created_by'] . '%');
            }]);
        }
        else $query->with('creator');

        if (!empty($filters['assignee'])) {
            $query->whereHas('assignee', function ($q) use ($filters) {
                $q->where('name', 'like', $filters['assignee'] . '%');
            });
            $query->with(['assignee' => function ($q) use ($filters) {
                $q->where('name', 'like', $filters['assignee'] . '%');
            }]);
        }
        else $query->with('assignee');

        if( !empty($filters['status'])) {
            $query->where('status', 'like', $filters['status']);
        }
        
        $sortableFields = ['title', 'due_date', 'status'];
        if (!empty($filters['sort_by']) && in_array($filters['sort_by'], $sortableFields)) {
            $sortDirection = !empty($filters['sort_order']) && in_array($filters['sort_order'], ['asc', 'desc']) ? $filters['sort_order'] : 'asc';
            $query->orderBy($filters['sort_by'], $sortDirection);
        } else {
            $query->orderBy('due_date', 'asc');
        }

        $totalCount = $query->count();

        $query->limit($perPage)->offset(($pageNumber-1) * $perPage);
        $tasks = $query->get();
        $count = $tasks->count();
        $result = [];
        foreach ($tasks as $activity) {
            $result[] = [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description ? $activity->description : null,
                'assignee' => $activity->assignee ? $activity->assignee->name : null,
                'due_date' => $activity->due_date ? Carbon::parse($activity->due_date)->toDateTimeString(): null,
                'status' => $activity->status,
                'created_by' => $activity->creator ? $activity->creator->name : null,
                'created_at' => Carbon::parse($activity->created_at)->toDateTimeString(),
                'updated_at' => Carbon::parse($activity->updated_at)->toDateTimeString(),
                'total_count' => $totalCount,
            ];
        }
        
        return $result;
    }

    public function listCreatedTasks($userId, array $filters = [], $pageNumber = 1, $perPage = 10)
    {
        $query = Task::query();

        $query = Task::where('created_by', $userId);

        if (!empty($filters['title'])) {
            $query->where('title', 'like', $filters['title'] . '%');
        }

        if (!empty($filters['from'])) {
            $query->where('due_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('due_date', '<=', $filters['to']);
        }

        if (!empty($filters['assignee'])) {
            $query->whereHas('assignee', function ($q) use ($filters) {
                $q->where('name', 'like', $filters['assignee'] . '%');
            });
            $query->with(['assignee' => function ($q) use ($filters) {
                $q->where('name', 'like', $filters['assignee'] . '%');
            }]);
        }
        else $query->with('assignee');

        if (!empty($filters['status'])) {
            $query->where('status', 'like', $filters['status']);
        }
        
        $sortableFields = ['title', 'due_date', 'status'];
        if (!empty($filters['sort_by']) && in_array($filters['sort_by'], $sortableFields)) {
            $sortDirection = !empty($filters['sort_order']) && in_array($filters['sort_order'], ['asc', 'desc']) ? $filters['sort_order'] : 'asc';
            $query->orderBy($filters['sort_by'], $sortDirection);
        } else {
            $query->orderBy('due_date', 'asc');
        }

        $totalCount = $query->count();

        $query->limit($perPage)->offset(($pageNumber-1) * $perPage);
        $tasks = $query->get();
        $count = $tasks->count();
        $result = [];
        foreach ($tasks as $activity) {
            $result[] = [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description ? $activity->description : null,
                'my_id' => $activity->created_by,
                'due_date' => $activity->due_date ? Carbon::parse($activity->due_date)->toDateTimeString(): null,
                'status' => $activity->status,
                'assignee' => $activity->assignee->name,
                'created_at' => Carbon::parse($activity->created_at)->toDateTimeString(),
                'updated_at' => Carbon::parse($activity->updated_at)->toDateTimeString(),
                'total_count' => $totalCount,
            ];
        }
        return $result;
    }

    public function getTodayTasks($userId = null)
    {
        $query = Task::whereDate('due_date', Carbon::today());

        if ($userId) {
            $query->where('assignee_id', $userId);
        }

        return $query->get();
    }
}