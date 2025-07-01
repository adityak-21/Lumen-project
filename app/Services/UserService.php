<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


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
            return ['error' => 'Invalid email or password', 'token' => null];
        }
        if (is_null($user->email_verified_at)) {
            return ['error' => 'Please confirm your email before logging in.', 'token' => null];
        }
        if (! $token = Auth::attempt($credentials)) {
            return ['error' => 'Invalid email/password', 'token' => null];
        }

        return ['error' => null, 'token' => $token];

    }

    public function performSoftDeleteUser($authUser, $id)
    {
        $result = ['id' => $id, 'status' => 'success', 'message' => ''];
        try {
            $userToDelete = User::findOrFail($id);

            if (!($authUser->roles->contains('role', 'admin'))) {
                $result['status'] = 'failed';
                $result['message'] = 'No permission';
                return $result;
            }
            if ($authUser->id == $id) {
                $result['status'] = 'failed';
                $result['message'] = 'Cannot delete yourself';
                return $result;
            }
            $userToDelete->deleted_by = $authUser->id;
            $userToDelete->save();
            $userToDelete->delete();
            $result['message'] = 'User deleted';
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $result['status'] = 'failed';
            $result['message'] = 'User not found';
        } catch (\Exception $e) {
            $result['status'] = 'failed';
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function performBulkDeleteUsers(array $userIds, $authUser)
    {
        try{
            DB::beginTransaction();
            $result = [];
            foreach ($userIds as $id) {
                $deleteResult = $this->performSoftDeleteUser($authUser, $id);
                if ($deleteResult['status'] !== 'success') {
                    DB::rollBack();
                    $result = ['status' => 'failed', 'message' => $deleteResult['message'], 'error' => $deleteResult['message']];
                    return $result;
                }
            }
            DB::commit();
            return $result = ['status' => 'success', 'message' => $deleteResult['message'], 'error' => null];
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            $result=[];
            $result = [
                'status' => 'failed', 
                'message' => 'Validation error. Please check the input fields.', 
                'errors' => $e->errors()
            ];
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            $result = [];
            return $result = [
                'status' => false,
                'message' => 'Server error.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function updateUserName($userId, $name)
    {
        $user = User::findOrFail($userId);
        $user->name = $name;
        $user->save();
        return ['status' => 'success', 'message' => 'Name updated successfully.'];
    }

    public function listUsers(array $filters = [], $pageNumber = 1, $perPage = 2)
    {
        $query = User::query();

        if (!empty($filters['name'])) {
            $query->where('name', 'like', $filters['name'] . '%');
        }

        if (!empty($filters['email'])) {
            $query->where('email', $filters['email']);
        }

        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('role', 'like', $filters['role'] . '%');
            });
            $query->with(['roles' => function ($q) use ($filters) {
                $q->where('role', 'like', $filters['role'] . '%');
            }]);
        }
        else $query->with('roles');

        $query->orderBy('name');

        $totalCount = $query->count();

        $query->limit($perPage)->offset(($pageNumber-1) * $perPage);
        $users = $query->get();
        $count = $users->count();
        $result = [];
        $result['users'] = [];

        foreach ($users as $user) {
            if ($user->roles->isEmpty()) {
                $result['users'][] = [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => null,
                    'created_by' => $user->created_by,
                    'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
                ];
            } else {
                foreach ($user->roles as $role) {
                    $result['users'][] = [
                        'id'   => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->roles->map(function($role) {
                            return [
                                'id' => $role->id,
                                'name' => $role->role,
                            ];
                        })->values(),
                        'created_by' => $user->created_by,
                        'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
                        'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }
        $result['count'] = $totalCount;
        return $result;

    }
}