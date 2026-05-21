<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthApiController extends Controller
{
    /**
     * POST /api/v1/auth/register/student
     * Register a new student
     */
    public function registerStudent(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        // Create student profile implicitly
        $user->profile()->create([
            'name' => $user->name,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Student registered successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * POST /api/v1/auth/register/recruiter
     * Register a new recruiter (awaits admin approval)
     */
    public function registerRecruiter(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'organization' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'recruiter',
        ]);

        // Create pending recruiter profile
        $user->recruiterProfile()->create([
            'organization' => $request->organization,
            'approval_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your recruiter account is pending approval. You will receive an email once approved.',
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     * Authenticate user and return token
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        // Recruiter approval checks
        if ($user->isRecruiter()) {
            $profile = $user->recruiterProfile;
            if ($profile) {
                $status = $profile->approval_status;

                if ($status === 'pending') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account is pending approval.',
                    ], 403);
                }

                if ($status === 'suspended') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account has been suspended.',
                    ], 403);
                }

                if ($status === 'rejected') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account application was not approved.',
                    ], 403);
                }
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * GET /api/v1/user
     * Fetch authenticated user details
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => new UserResource($request->user()->load(['profile', 'recruiterProfile'])),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     * Revoke tokens
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
