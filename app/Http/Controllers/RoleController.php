<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return response(Role::all());
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'role' => 'required|unique:roles,role',
        ]);

        $role = Role::create([
            'role' => $request->input('role'),
        ]);
        return response($role, 201);
    }

    public function show($id)
    {
        $role = Role::find($id);
        if (! $role) {
            return response(['error' => 'Role not found'], 404);
        }
        return response()->json($role);
    }

    public function destroy($id)
    {
        $role = Role::find($id);
        if (! $role) {
            return response(['error' => 'Role not found'], 404);
        }
        $role->delete();
        return response(['message' => 'Role deleted']);
    }
}