<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Validator;
use App\Services\TaskService;
use App\Events\TaskRegistered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Events\PrivateMessage;
use App\Services\NotificationService;
use App\Models\Notification;
use App\Events\TaskRelatedMessages;

use Carbon\Carbon;

class TaskController extends Controller
{

    protected $taskService;
    public function __construct(TaskService $taskService, NotificationService $notificationService)
    {
        $this->taskService = $taskService;
        $this->notificationService = $notificationService;
    }
    public function createTask(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $data = $request->only(['title', 'description', 'due_date', 'assignee_id']);
        $data['due_date'] = isset($data['due_date']) && $data['due_date'] ? Carbon::parse($data['due_date'])->toDateTimeString() : null;
        try {
            $task = $this->taskService->createTask($data['title'], $data['assignee_id'], Auth::user()->id, $data);

            event(new TaskRegistered($task));
            $notif = $this->notificationService->createNotification(
                $task->assignee_id,
                'New Task Assigned',
                'You have been assigned a new task: ' . $task->title
            );
            $notificationId = $notif->id;
            event(new TaskRelatedMessages(
                'New Task Assigned',
                'You have been assigned a new task: ' . $task->title,
                $task->assignee_id,
                $notificationId
            ));
            return response()->json([
                'success' => true,
                'message' => 'Task created successfully.',
                'task' => $task,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task.',
                'error' => $e->getMessage(),
            ], 500);
        }
        
        
    }

    public function updateTaskTitle(Request $request, $taskId)
    {
        $this->validate($request, [
            'title' => 'required|string|max:255',
        ]);

        try {
            $oldTitle = Task::findOrFail($taskId)->title;
            $task = $this->taskService->updateTaskTitle($taskId, $request->input('title'));
            $notif = $this->notificationService->createNotification(
                $task->assignee_id,
                'Task Title Updated',
                'Your task title has been updated from ' . $oldTitle . ' to: ' . $task->title
            );
            $notificationId = $notif->id;
            event(new TaskRelatedMessages(
                'Task Title Updated',
                'Your task title has been updated from ' . $oldTitle . ' to: ' . $task->title,
                $task->assignee_id,
                $notificationId
            ));
            return response()->json([
                'success' => true,
                'message' => 'Task title updated successfully.',
                'task' => $task,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task title.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateTaskDescription(Request $request, $taskId)
    {
        $this->validate($request, [
            'description' => 'required|string',
        ]);

        try {
            $task = $this->taskService->updateTaskDescription($taskId, $request->input('description'));
            $notif = $this->notificationService->createNotification(
                $task->assignee_id,
                'Task Description Updated',
                'Your task description for ' . $task->title . ' has been updated to: ' . $task->description
            );
            $notificationId = $notif->id;
            event(new TaskRelatedMessages(
                'Task Description Updated',
                'Your task description for ' . $task->title . ' has been updated to: ' . $task->description,
                $task->assignee_id,
                $notificationId
            ));
            return response()->json([
                'success' => true,
                'message' => 'Task description updated successfully.',
                'task' => $task,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task description.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateTaskDueDate(Request $request, $taskId)
    {
        $this->validate($request, [
            'due_date' => 'required|date',
        ]);

        try {
            $task = $this->taskService->updateTaskDueDate($taskId, Carbon::parse($request->input('due_date'))->toDateTimeString());
            $notif = $this->notificationService->createNotification(
                $task->assignee_id,
                'Task Due Date Updated',
                'Your task Due Date for ' . $task->title . ' has been updated to: ' . $task->due_date
            );
            $notificationId = $notif->id;
            event(new TaskRelatedMessages(
                'Task Due Date Updated',
                'Your task Due Date for ' . $task->title . ' has been updated to: ' . $task->due_date,
                $task->assignee_id,
                $notificationId
            ));
            return response()->json([
                'success' => true,
                'message' => 'Task due date updated successfully.',
                'task' => $task,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task due date.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateTaskStatus(Request $request, $taskId)
    {
        $this->validate($request, [
            'status' => 'required|string|in:assigned,in_progress,verified,completed',
        ]);

        try {
            $task = $this->taskService->updateTaskStatus($taskId, $request->input('status'));
            $notif = $this->notificationService->createNotification(
                $task->assignee_id,
                'Task Status Updated',
                'Your task Status for ' . $task->title . ' has been updated to: ' . $task->status
            );
            $notificationId = $notif->id;
            event(new TaskRelatedMessages(
                'Task Status Updated',
                'Your task Status for ' . $task->title . ' has been updated to: ' . $task->status,
                $task->assignee_id,
                $notificationId
            ));
            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully.',
                'task' => $task,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteTask($taskId)
    {
        try {
            
            $result = $this->taskService->deleteTask($taskId);
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listMyTasks(Request $request)
    {
        $this->validate($request, [
            'title' => 'string|max:255|nullable',
            'created_by' => 'string|nullable',
            'from' => 'date|nullable',
            'to' => 'date|nullable',
            'status' => 'string|in:assigned,in_progress,verified,completed|nullable',
            'pagenumber' => 'integer|min:1|nullable',
            'perpage' => 'integer|min:1|nullable',
            'sort_by' => 'string|in:title,due_date,status|nullable',
            'sort_order' => 'string|in:asc,desc|nullable',
        ]);
        $filters = [];

        $filters['title'] = $request->input('title');
        $filters['created_by'] = $request->input('created_by');
        $filters['from'] = $request->input('from');
        $filters['to'] = $request->input('to');
        $filters['status'] = $request->input('status');
        $pageNumber = $request->input('pagenumber', 1);
        $perPage = $request->input('perpage', 10);
        $filters['from'] = isset($filters['from']) && $filters['from'] ? Carbon::parse($filters['from'])->toDateTimeString() : null;
        $filters['to'] = isset($filters['to']) && $filters['to'] ? Carbon::parse($filters['to'])->toDateTimeString() : null;
        $filters['sort_by'] = $request->input('sort_by', 'due_date');
        $filters['sort_order'] = $request->input('sort_order', 'asc');

        try {
            $tasks = $this->taskService->listMyTasks(Auth::user()->id, $filters, $pageNumber, $perPage);
            return response()->json([
                'success' => true,
                'tasks' => $tasks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listCreatedTasks(Request $request)
    {
        $this->validate($request, [
            'title' => 'string|max:255|nullable',
            'assignee' => 'string|nullable',
            'from' => 'date|nullable',
            'to' => 'date|nullable',
            'status' => 'string|in:assigned,in_progress,verified,completed|nullable',
            'pagenumber' => 'integer|min:1|nullable',
            'perpage' => 'integer|min:1|nullable',
            'sort_by' => 'string|in:title,due_date,status|nullable',
            'sort_order' => 'string|in:asc,desc|nullable',
        ]);
        $filters = [];
        $filters['title'] = $request->input('title');
        $filters['assignee'] = $request->input('assignee');
        $filters['from'] = $request->input('from');
        $filters['to'] = $request->input('to');
        $filters['status'] = $request->input('status');
        $pageNumber = $request->input('pagenumber', 1);
        $perPage = $request->input('perpage', 10);
        $filters['from'] = isset($filters['from']) && $filters['from'] ? Carbon::parse($filters['from'])->toDateTimeString() : null;
        $filters['to'] = isset($filters['to']) && $filters['to'] ? Carbon::parse($filters['to'])->toDateTimeString() : null;
        $filters['sort_by'] = $request->input('sort_by', 'due_date');
        $filters['sort_order'] = $request->input('sort_order', 'asc');

        try {
            $tasks = $this->taskService->listCreatedTasks(Auth::user()->id, $filters, $pageNumber, $perPage);
            return response()->json([
                'success' => true,
                'tasks' => $tasks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listAllTasks(Request $request)
    {
        $this->validate($request, [
            'title' => 'string|max:255|nullable',
            'created_by' => 'string|nullable',
            'assignee' => 'string|nullable',
            'from' => 'date|nullable',
            'to' => 'date|nullable',
            'status' => 'string|in:assigned,in_progress,verified,completed|nullable',
            'pagenumber' => 'integer|min:1|nullable',
            'perpage' => 'integer|min:1|nullable',
        ]);
        $filters = [];

        $filters['title'] = $request->input('title');
        $filters['created_by'] = $request->input('created_by');
        $filters['assignee'] = $request->input('assignee');
        $filters['from'] = $request->input('from');
        $filters['to'] = $request->input('to');
        $filters['status'] = $request->input('status');
        $pageNumber = $request->input('pagenumber', 1);
        $perPage = $request->input('perpage', 10);
        $filters['from'] = isset($filters['from']) && $filters['from'] ? Carbon::parse($filters['from'])->toDateTimeString() : null;
        $filters['to'] = isset($filters['to']) && $filters['to'] ? Carbon::parse($filters['to'])->toDateTimeString() : null;
        $filters['sort_by'] = $request->input('sort_by', 'due_date');
        $filters['sort_order'] = $request->input('sort_order', 'asc');

        if(!Gate::allows('is-Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view all tasks.',
            ], 403);
        }
        try {
            $tasks = $this->taskService->listAllTasks(Auth::user()->id, $filters, $pageNumber, $perPage);
            return response()->json([
                'success' => true,
                'tasks' => $tasks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }    
}