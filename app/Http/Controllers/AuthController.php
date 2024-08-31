<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }


        if (!$user->is_verified) {
            return response()->json(['message' => 'Account not verified'], 403);
        }

        $token = $user->createToken('Personal Access Token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
        ]);
    }


    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|max:255',
            'phone' => 'required|unique:users,phone'
        ]);

        $verificationCode = rand(100000, 999999);

        $user = User::create([
            'phone' => $request->phone,
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'verification_code' => $verificationCode,
            'is_verified' => false,
        ]);

        if ($user) {
            Log::info('Verification code for user ' . $user->id . ': ' . $verificationCode);
            $token = $user->createToken($user->name . 'Auth-Token')->plainTextToken;
            return response()->json([
                'message' => 'Registration successful',
                'token_type' => 'Bearer',
                'token' => $token,
                'verification_code' => $verificationCode,
            ], 201);
        } else {
            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'verification_code' => 'required|digits:6',
        ]);


        $user = User::where('phone', $request->phone)->first();

        if (!$user || $user->verification_code !== $request->verification_code) {
            return response()->json(['message' => 'Invalid or expired verification code'], 400);
        }
        $user->is_verified = true;
        $user->verification_code = null;
        $user->save();

        return response()->json(['message' => 'Account verified successfully']);
    }
}
