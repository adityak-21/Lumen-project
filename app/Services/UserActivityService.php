<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserActivityService
{
    public function loginActivity($userId, $loginTime = null)
    {
        return ActivityLog::create([
            'user_id' => $userId,
            'login_time' => $loginTime ?: Carbon::now(),
            'logout_time' => null,
            'duration' => null,
        ]);
    }

    public function logoutActivity($userId, $logoutTime = null)
    {
        $activity = ActivityLog::where('user_id', $userId)
            ->whereNull('logout_time')
            ->orderBy('login_time', 'desc')
            ->first();

        if ($activity) {
            $activity->logout_time = $logoutTime ?: Carbon::now();
            $activity->duration = Carbon::parse($activity->login_time)->diffInSeconds($activity->logout_time);
            $activity->save();
        }

        return $activity;
    }

    public function listUserActivities(array $filters = [], $pageNumber = 1, $perPage = 2)
    {
        $query = ActivityLog::query();

        if (!empty($filters['name'])) {
            $query->whereHas('users', function ($q) use ($filters) {
                $q->where('name', 'like', $filters['name']);
            });
            $query->with(['users' => function ($q) use ($filters) {
                $q->where('name', 'like', $filters['name'] . '%');
            }]);
        } else {
            $query->with('users');
        }

        if (!empty($filters['from'])) {
            $query->where('login_time', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('logout_time', '<=', $filters['to']);
        }

        $query->orderBy('login_time', 'desc');
        $query->limit($perPage)->offset(($pageNumber-1) * $perPage);

        $list = $query->get();

        $result = [];

        foreach ($list as $activity) {
            $result[] = [
                'user_id' => $activity->user_id,
                'user_name' => $activity->users ? $activity->users->name : null,
                'login_time' => $activity->login_time,
                'logout_time' => $activity->logout_time,
                'duration' => $activity->duration,
            ];
        }
        
        return $result;
    }
}