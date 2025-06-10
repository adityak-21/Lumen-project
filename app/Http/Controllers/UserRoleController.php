<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    public function assignRoles(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $roleIds = $request->input('roles');
        $roleIds = is_array($roleIds) ? $roleIds : [$roleIds];
        $user->roles()->syncWithoutDetaching($roleIds);

        return response(['message' => 'Roles assigned successfully.']);
    }

    public function removeRole($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $user->roles()->detach($roleId);

        return response(['message' => 'Role removed successfully.']);
    }
}