<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Services\RoleService;

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
        $user = User::findOrFail($userId);
        $roleIds = $request->input('roles');
        $roleIds = is_array($roleIds) ? $roleIds : [$roleIds];
        $result = $this->roleService->AssignUserRoles($userId, $roleIds);
        if(!$result['error']) return response(['message' => 'Roles assigned successfully.'], 200);
        else return response(['error' => $result['message']], 500);
    }

    public function removeRole($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);
        if ($result['error']) {
            return response(['error' => $result['message']], 500);
        }
        if (!$user->roles->contains($roleId)) {
            return response(['error' => 'Role not assigned to user.'], 404);
        }
        $result = $this->roleService->RemoveUserRole($userId, $roleId);
        if ($result['error']) {
            return response(['error' => $result['message']], 500);
        }
        return response(['message' => 'Role removed successfully.'], 200);
    }
}