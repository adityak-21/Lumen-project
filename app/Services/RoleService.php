<?php

namespace App\Services;
use App\Models\Role;
use App\Models\User;

class RoleService
{
    public function AssignUserRoles(int $userId, array $roleIds)
    {
        try{
            $user = User::findOrFail($userId);
            $user->roles()->syncWithoutDetaching($roleIds);
            return ['error' => null, 'message' => 'Roles assigned successfully.'];
        }
        catch (\Exception $e) {
            return ['error' => 'Failed to assign roles: ' . $e->getMessage()];
        }
    }
    public function RemoveUserRole(int $userId, int $roleId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->roles()->detach($roleId);
            return ['error' => null, 'message' => 'Role removed successfully.'];
        } catch (\Exception $e) {
            return ['error' => 'Failed to remove role: ' . $e->getMessage()];
        }
    }

    public function ChangeRoles(int $userId, array $roleIds)
    {
        try {
            $user = User::findOrFail($userId);
            $user->roles()->sync($roleIds);
            return ['error' => null, 'message' => 'Roles changed successfully.'];
        } catch (\Exception $e) {
            return ['error' => 'Failed to assign roles: ' . $e->getMessage()];
        }
    }
}