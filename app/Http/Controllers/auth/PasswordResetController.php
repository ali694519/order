<?php

namespace App\Http\Controllers\auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
  /**
   * @OA\Post(
   *     path="/forgot-password",
   *     summary="Request password reset",
   *     description="Send a password reset link to the user's email.",
   *  *   tags={"Authentication"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                 type="object",
   *                 required={"email"},
   *                 @OA\Property(
   *                     property="email",
   *                     type="string",
   *                     format="email",
   *                     example="user@example.com"
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Password reset link sent",
   *         @OA\JsonContent(
   *             @OA\Property(
   *                 property="message",
   *                 type="string",
   *                 example="Password reset link sent to your email."
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="User not found"
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Invalid input"
   *     )
   * )
   */
  public function sendResetLinkEmail(Request $request)
  {
    $request->validate([
      'email' => 'required|email|exists:AspNetUsers,Email',
    ]);

    $user = User::where('Email', $request->email)->first();
    // Create a password reset token
    $token = Str::random(60);
    DB::table('password_reset_tokens')->updateOrInsert(
      ['email' => $request->email],
      [
        'token' => Hash::make($token),
        'created_at' => now()
      ]
    );
    // Send the email (adjust the view and logic as needed)
    Mail::raw(
      "To reset your password, please use the following token: $token",
      function ($message) use ($request) {
        $message->to($request->email);
        $message->subject('Password Reset Request');
      }
    );

    return response()->json(
      [
        'message' => 'Password reset link sent to your email.'
      ],
      200
    );
  }

  /**
   * @OA\Post(
   *     path="/reset-password",
   *     summary="Reset password",
   *     description="Reset the user's password using the token sent to their email.",
   *   *     tags={"Authentication"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                 type="object",
   *                 required={"email", "token", "password"},
   *                 @OA\Property(
   *                     property="email",
   *                     type="string",
   *                     format="email",
   *                     example="user@example.com"
   *                 ),
   *                 @OA\Property(
   *                     property="token",
   *                     type="string",
   *                     example="random-token"
   *                 ),
   *                 @OA\Property(
   *                     property="password",
   *                     type="string",
   *                     format="password",
   *                     example="newpassword"
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Password successfully reset",
   *         @OA\JsonContent(
   *             @OA\Property(
   *                 property="message",
   *                 type="string",
   *                 example="Password successfully reset."
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Invalid token or request"
   *     )
   * )
   */
  public function resetPassword(Request $request)
  {
    $request->validate([
      'email' => 'required|email|exists:AspNetUsers,Email',
      'token' => 'required',
      'PasswordHash' => 'required|confirmed|min:6',
    ]);

    $passwordReset = DB::table('password_reset_tokens')
      ->where('email', $request->email)
      ->first();

    if (!$passwordReset || !Hash::check($request->token, $passwordReset->token)) {
      return response()->json(['message' => 'Invalid token or request'], 400);
    }

    $user = User::where('Email', $request->email)->first();
    $user->PasswordHash = Hash::make($request->password);
    $user->save();

    DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    return response()->json(['message' => 'Password successfully reset.'], 200);
  }
}
