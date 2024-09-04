<?php

namespace App\Http\Controllers\auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function changeUserRole(Request $request)
    {
        $validatedData = $request->validate([
            'userId' => 'required|exists:AspNetUsers,Id',
            'role' => 'required|string|exists:AspNetRoles,Name',
        ]);

        $user = User::findOrFail($validatedData['userId']);

        $role = Role::where('Name', $validatedData['role'])->first();
        if ($role) {
            $user->roles()->sync([$role->Id]);
            return response()->json([
                'message' => 'User role updated successfully',
            ]);
        }

        return response()->json(
            ['message' => 'Role not found'],
            404
        );
    }
}
