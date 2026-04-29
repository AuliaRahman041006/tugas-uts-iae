<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * POST /api/register
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully. Silakan login untuk mendapatkan token.',
            'data'    => $user,
        ], 201);
    }

    /**
     * Login user and return token.
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'user'  => $user,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout user (revoke current token).
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user profile.
     * GET /api/profile
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => $request->user(),
        ]);
    }

    /**
     * Verify token — internal endpoint for other microservices.
     * GET /api/user/verify
     *
     * Called by Product Service & Order Service to validate user tokens.
     */
    public function verify(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => $request->user(),
        ]);
    }
}
