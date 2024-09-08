<?php

namespace App\Http\Controllers\auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
  /**
   * @OA\Post(
   *     path="/api/user/change-role",
   *     summary="Change user role",
   *     description="Update the role of a user. The role will be assigned to the user based on the provided role name.",
   * *     tags={"Authentication"},
   *     security={{"bearerAuth": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"userId", "role"},
   *             @OA\Property(property="userId", type="integer", example=1, description="ID of the user whose role is to be updated"),
   *             @OA\Property(property="role", type="string", example="Admins", description="Name of the role to be assigned")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="User role updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User role updated successfully")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Role not found or User not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Role not found")
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Invalid input",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid.")
   *         )
   *     )
   * )
   */
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
