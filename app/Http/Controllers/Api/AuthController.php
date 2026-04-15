<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Obtain a Bearer token",
     *     description="Authenticates a user and returns a Sanctum API token. Rate-limited to 5 attempts per minute.",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"email","password","device_name"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@witbo.com.cy"),
     *             @OA\Property(property="password", type="string", format="password", example="secret"),
     *             @OA\Property(property="device_name", type="string", example="iPhone 15 Pro")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="1|abc123..."),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="workspace", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Invalid credentials", @OA\JsonContent(ref="#/components/schemas/ErrorValidation")),
     *     @OA\Response(response=429, description="Too many attempts")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'workspace' => $user->currentWorkspaceRecord(),
        ]);
    }

    /**
     * Revoke the current token (logout).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
