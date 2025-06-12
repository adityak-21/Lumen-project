<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;


class UserService
{
    public function createUser(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'confirmation_token' => Str::random(32),
        ]);
    }

    public function verifyUser(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();
            
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response(['error' => 'Invalid email or password'], 401);
        }
        if (is_null($user->email_verified_at)) {
            return response(['error' => 'Please confirm your email before logging in.'], 403);
        }
        if (! $token = Auth::attempt($credentials)) {
            return response(['error' => 'Invalid email/password'], 401);
        }

        return $token;

    }

    public function listUsers(array $filters = [], $pageNumber = 1, $perPage = 2)
    {
        $query = User::query();

        if (isset($filters['name'])) {
            $query->where('name', 'like', $filters['name'] . '%');
        }

        if (isset($filters['email'])) {
            $query->where('email', $filters['email']);
        }

        if (isset($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('role', 'like', $filters['role'] . '%');
            });
            $query->with(['roles' => function ($q) use ($filters) {
                $q->where('role', 'like', $filters['role'] . '%');
            }]);
        }
        else $query->with('roles');

        $query->orderBy('name');

        $query->limit($perPage)->offset(($pageNumber-1) * $perPage);
        $users = $query->get();

        $result = [];

        foreach ($users as $user) {
            if ($user->roles->isEmpty()) {
                $result[] = [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'role' => null,
                ];
            } else {
                foreach ($user->roles as $role) {
                    $result[] = [
                        'id'   => $user->id,
                        'name' => $user->name,
                        'role' => $role->role,
                    ];
                }
            }
        }
        return $result;

    }
}