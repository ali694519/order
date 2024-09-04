<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{

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
            'data' => $user
        ]);
    }

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
