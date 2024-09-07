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
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="User login",
     *     description="Authenticates a user and returns an access token",
     * *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"Email", "PasswordHash"},
     *             @OA\Property(property="Email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="PasswordHash", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="Id", type="integer", example=1),
     *                 @OA\Property(property="Email", type="string", example="user@example.com"),
     *                 @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJpYXQiOjE2MzEzMjE0NDIsImV4cCI6MTYzMTMyNzg0Mn0.Swbd_YR-W-Z0pSC4wwk3kc0AG9-tMjErVVy5Lzo2GFE"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="Email", type="array", @OA\Items(type="string"), example={"The Email field is required."}),
     *             @OA\Property(property="PasswordHash", type="array", @OA\Items(type="string"), example={"The PasswordHash field is required."})
     *         )
     *     )
     * )
     */
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
    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="User registration",
     *     description="Registers a new user with email and password",
     * *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"Email", "PasswordHash", "PasswordHash_confirmation"},
     *             @OA\Property(property="Email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="PasswordHash", type="string", format="password", example="password123"),
     *             @OA\Property(property="PasswordHash_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User successfully registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User successfully registered"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="Id", type="integer", example=1),
     *                 @OA\Property(property="Email", type="string", example="user@example.com"),
     *                 @OA\Property(property="UserName", type="string", example="username")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="Email", type="array", @OA\Items(type="string"), example={"The Email field is required."}),
     *             @OA\Property(property="PasswordHash", type="array", @OA\Items(type="string"), example={"The PasswordHash field is required.", "The PasswordHash confirmation does not match."})
     *         )
     *     )
     * )
     */
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
    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout the current user",
     *     description="Logs out the currently authenticated user and invalidates their token",
     * *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful logout",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User successfully signed out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }
    /**
     * @OA\Get(
     *     path="/api/user-profile",
     *     summary="Get user profile",
     *     description="Retrieve the profile information of the currently authenticated user",
     * *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="Id", type="integer", example=1),
     *             @OA\Property(property="Email", type="string", example="user@example.com"),
     *             @OA\Property(property="UserName", type="string", example="username"),
     *             @OA\Property(property="PhoneNumber", type="string", example="123-456-7890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
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
