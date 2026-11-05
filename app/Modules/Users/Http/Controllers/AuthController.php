<?php

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        // role is not mass-assignable, so every self-registered user is a customer.
        $user = User::create($data);
        $token = auth('api')->login($user);

        return $this->tokenResponse($token, $user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = auth('api')->attempt($credentials);

        if (! $token) {
            // Same message for "no such email" and "wrong password": do not reveal which accounts exist.
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return $this->tokenResponse($token, auth('api')->user());
    }

    public function me(): JsonResponse
    {
        return response()->json(['data' => auth('api')->user()]);
    }

    public function logout(): JsonResponse
    {
        // Invalidates the token, so it cannot be reused after logout.
        auth('api')->logout();

        return response()->json(['message' => 'Logged out.']);
    }

    private function tokenResponse(string $token, $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], $status);
    }
}
