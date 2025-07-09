<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Services\RoleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;


class UserRoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function assignRoles(Request $request, $userId)
    {
        $this->validate($request, [
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);
        try {
            $authUser = Auth::user();
            if (!Gate::allows('is-Admin', $authUser)) {
                return response(['error' => 'No permission'], 403);
            }
            $user = User::findOrFail($userId);
            $roleIds = $request->input('roles');
            $roleIds = is_array($roleIds) ? $roleIds : [$roleIds];
            $result = $this->roleService->AssignUserRoles($userId, $roleIds);
            if(!$result['error']) return response(['message' => 'Roles assigned successfully.'], 200);
            else return response(['error' => $result['message']], 500);
        } catch (\Exception $e) {
            return response(['error' => 'Unauthorized'], 401);
        }
    }

    public function removeRole($userId, $roleId)
    {
        \Log::info('Removing role', [
            'userId' => $userId,
            'roleId' => $roleId
        ]);
        $authUser = Auth::user();
        if (!Gate::allows('is-Admin', $authUser)) {
            return response(['error' => 'No permission'], 403);
        }
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);
        if (!$user->roles->contains($roleId)) {
            return response(['error' => 'Role not assigned to user.'], 404);
        }
        $result = $this->roleService->RemoveUserRole($userId, $roleId);
        if ($result['error']) {
            return response(['error' => $result['message']], 500);
        }
        return response(['message' => 'Role removed successfully.'], 200);
    }

    public function changeRoles(Request $request, $userId)
    {
        $this->validate($request, [
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);
        try {
            $authUser = Auth::user();
            if (!Gate::allows('is-Admin', $authUser)) {
                return response(['error' => 'No permission'], 403);
            }
            $user = User::findOrFail($userId);
            $roleIds = $request->input('roles');
            $roleIds = is_array($roleIds) ? $roleIds : [$roleIds];
            $result = $this->roleService->ChangeRoles($userId, $roleIds);
            if(!$result['error']) return response(['message' => 'Roles changed successfully.'], 200);
            else return response(['error' => $result['message']], 500);
        } catch (\Exception $e) {
            return response(['error' => 'Unauthorized'], 401);
        }
    }
}