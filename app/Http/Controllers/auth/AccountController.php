<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/change-email",
     *     summary="Change user email",
     *     description="Update the email address of the currently authenticated user.",
     * *     tags={"Account Sittings"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"Email"},
     *             @OA\Property(property="Email", type="string", format="email", example="newemail@example.com", description="New email address for the user.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email successfully updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email successfully updated"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="Id", type="integer", example=1),
     *                 @OA\Property(property="Email", type="string", example="newemail@example.com"),
     *                 @OA\Property(property="UserName", type="string", example="newemail@example.com"),
     *                 @OA\Property(property="PhoneNumber", type="string", example="1234567890")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="Email", type="array", @OA\Items(type="string", example="The Email has already been taken."))
     *             )
     *         )
     *     )
     * )
     */
    public function changeEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Email' => 'required|string|email|max:100|unique:AspNetUsers,Email',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        /** @var \App\Models\User $user **/
        $user = auth()->user();
        $user->Email = $request->Email;
        $user->UserName = $request->Email;
        $user->NormalizedEmail = Str::upper($request->Email);
        $user->NormalizedUserName = Str::upper($request->Email);
        $user->save();
        return response()->json([
            'message' => 'Email successfully updated',
            'data' => [
                'Id' => $user->Id,
                'Email' => $user->Email,
                'UserName' => $user->UserName,
                'PhoneNumber' => $user->PhoneNumber,
            ]
        ]);
    }
    /**
     * @OA\Post(
     *     path="/api/change-password",
     *     summary="Change user password",
     *     description="Allow the authenticated user to change their current password.",
     * *     tags={"Account Sittings"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "new_password"},
     *             @OA\Property(property="current_password", type="string", format="password", example="currentPassword123", description="Current password of the user."),
     *             @OA\Property(property="new_password", type="string", format="password", example="newPassword123", description="New password for the user. Must be at least 6 characters long."),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="newPassword123", description="Confirmation of the new password.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password successfully updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password successfully updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Current password is incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Current password is incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="current_password", type="array", @OA\Items(type="string", example="The current password field is required.")),
     *                 @OA\Property(property="new_password", type="array", @OA\Items(type="string", example="The new password field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        /** @var \App\Models\User $user **/

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->PasswordHash)) {
            return response()->json(['error' => 'Current password is incorrect'], 401);
        }

        $user->PasswordHash = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password successfully updated']);
    }
    /**
     * @OA\Post(
     *     path="/api/add-phone-number",
     *     summary="Add phone number to user profile",
     *     description="Allow the authenticated user to add or update their phone number.",
     * *     tags={"Account Sittings"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"PhoneNumber"},
     *             @OA\Property(property="PhoneNumber", type="string", example="123-456-7890", description="The phone number to be added or updated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phone number successfully added",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Phone number successfully added")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="PhoneNumber", type="array", @OA\Items(type="string", example="The PhoneNumber field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function addPhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'PhoneNumber' => 'required|string|max:15',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        /** @var \App\Models\User $user **/
        $user = auth()->user();
        $user->PhoneNumber = $request->PhoneNumber;
        $user->PhoneNumberConfirmed = true;
        $user->save();

        return response()->json(['message' => 'Phone number successfully added']);
    }
}
