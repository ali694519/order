<?php

namespace App\Http\Controllers\auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Email' => 'required|email',
            'PasswordHash' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(
                $validator->errors(),
                422
            );
        }
        $credentials = $validator->validated();
        $user = User::where('Email', $credentials['Email'])->first();

        if (!$user || !Hash::check($credentials['PasswordHash'], $user->PasswordHash)) {
            return response()->json([
                'error' =>
                'Unauthorized'
            ], 401);
        }
        $token = auth()->login($user);
        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'Id' => $user->Id,
                'Email' => $user->Email,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ]
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Email' => 'required|string|email|max:100|unique:AspNetUsers',
            'PasswordHash' => 'required|string|confirmed|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $data = $validator->validated();
        $data['PasswordHash'] = Hash::make($data['PasswordHash']);
        $user = User::create($data);
        $normalUserRole = Role::where('NormalizedName', 'NORMALUSER')->first();

        if ($normalUserRole) {
            DB::table('AspNetUserRoles')->insert([
                'UserId' => $user->Id,
                'RoleId' => $normalUserRole->Id,
            ]);
        }
        return response()->json([
            'message' => 'User successfully registered',
            'data' => [
                'Id' => $user->Id,
                'Email' => $user->Email,
                'UserName' => $user->UserName
            ]
        ], 201);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }
    public function userProfile()
    {
        $user = auth()->user();

        return response()->json([
            'Id' => $user->Id,
            'Email' => $user->Email,
            'UserName' => $user->UserName,
            'PhoneNumber' => $user->PhoneNumber,
        ]);
    }
}
