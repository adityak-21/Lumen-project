<?php

namespace App\Http\Controllers;
use App\Events\Message;
use App\Events\PrivateMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Events\TaskMessage;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class NotificationsController extends Controller
{
    public function sendMessage(Request $request)
    {
        $this->validate($request, [
            'message' => 'required|string|max:255',
        ]);

        $message = $request->input('message');

        event(new Message($message));

        return response(['status' => 'Message sent successfully!']);
    }

    public function sendToUser(Request $request)
    {
        // $this->validate($request, [
        //     'user_id' => 'required|integer',
        //     'message' => 'required|string',
        // ]);

        // event(new TaskMessage([
        //     'user_id' => $request->input('user_id'),
        //     'content' => $request->input('message'),
        // ]));

        // return response(['status' => 'Message sent']);
        $this->validate($request, [
            'user_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $message = $request->input('message');
        $userId = $request->input('user_id');

        event(new PrivateMessage(
            $message,
            $userId
        ));

        return response(['status' => 'Message sent']);
    }

    public function listNotifications()
    {
        $user = Auth::user();

        if (!$user) {
            return response(['error' => 'Unauthorized'], 401);
        }

        $notifications = User::with(['notifications' => function ($query) {
            $query->where('created_at', '>=', Carbon::now()->subDays(3));
            $query->orderBy('created_at', 'desc');
        }])->find($user->id)->notifications;

        return response($notifications);
    }

    public function markAsRead($notificationId)
    {
        $user = Auth::user();

        if (!$user) {
            return response(['error' => 'Unauthorized'], 401);
        }

        $notification = Notification::find($notificationId);

        if (!$notification) {
            return response(['error' => 'Notification not found'], 404);
        }

        $notification->is_read = true;
        $notification->save();

        return response(['status' => 'Notification marked as read']);
    }
}