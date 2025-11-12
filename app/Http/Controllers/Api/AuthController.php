<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Response;

class AuthController extends Controller
{
    // Register User
    public function register(RegisterRequest $request):JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 201);
    }
    
    // Login User
    public function login(LoginRequest $request):JsonResponse
    {
        $user = User::where('email', $request->email)->first();
        
        if(!$user || !Hash::check($request->password, $user->password)){
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return Response::json([
            'success'=> true,
            'message' => 'User logged in successfully',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 200);
    }
    
    // Get Authenticated User
    public function user(Request $request):JsonResponse
    {
        $user = $request->user();

        return Response::json([
            'success' => true,
            'message' => 'user data retrieved successfully',
            'data' => [
                'user' => $request->user(),
            ]
        ],200);
    }
    
    //Logout User
    public function logout(Request $request):JsonResponse
    {
        $request->user()->tokens()->delete();

        return Response::json([
            'success' => true,
            'message' => 'User logged out successfully',
        ], 200);
    }
}
