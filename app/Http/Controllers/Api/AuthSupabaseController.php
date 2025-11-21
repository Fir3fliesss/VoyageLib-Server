<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Response;

class AuthSupabaseController extends Controller
{
    protected $supabaseAuth;

    public function __construct(SupabaseAuthService $supabaseAuth)
    {
        $this->supabaseAuth = $supabaseAuth;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email'=> 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'role' => 'nullable|in:user,staff,admin' // Optional role field
        ]);

        if ($validator->fails()) {
            return Response::json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Set default role to user if not provided
            $role = $request->role ?? 'user';
            
            // Register in Supabase with role in user_metadata
            $userData = $this->supabaseAuth->register(
                $request->email,
                $request->password,
                [
                    'name' => $request->name,
                    'role' => $role,
                    'phone' => $request->phone,
                    'address' => $request->address
                ]
            );

            // Create user in local database
            $localUser = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'role' => $role,
                'phone' => $request->phone,
                'address' => $request->address,
                'supabase_id' => $userData->user->id ?? $userData->id ?? null
            ]);

            $responseData = [];
            if (isset($userData->user)) {
                $responseData['user'] = $userData->user;
            }
            if (isset($userData->id)) {
                $responseData['id'] = $userData->id;
            }
            $responseData['local_user'] = $localUser;

            return Response::json([
                'success' => true,
                'message' => 'Registration Successful.',
                'data' => $responseData
            ], 201);
        } catch (Exception $e) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Response::json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $authData = $this->supabaseAuth->login(
                $request->email,
                $request->password,
            );
            
            // Get local user data with role
            $localUser = \App\Models\User::where('email', $request->email)->first();
            
            // Include role in user data
            if ($localUser) {
                $authData->user->role = $localUser->role;
                $authData->user->phone = $localUser->phone;
                $authData->user->address = $localUser->address;
            }
            
            return Response::json([
                'success' => true,
                'message' => 'Login Successful',
                'data' => [
                    'user' => $authData->user,
                    'role' => $localUser ? $localUser->role : 'user', // Add role separately for clarity
                    'access_token' => $authData->access_token,
                    'refresh_token' => $authData->refresh_token,
                ]
            ], 200);
        } catch (Exception $e) {
            return Response::json([
                'success' => false,
                'message' => 'email or password is incorrect'
            ], 401);
        }
    }
    
    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();
            $this->supabaseAuth->signOut($token);
            
            return Response::json([
                'success' => true,
                'message' => 'Logout Successful',
            ], 200);
        } catch (Exception $e) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    public function profile(Request $request) {
        try {
            // Get user from request (set by middleware)
            $localUser = $request->user();
            
            if (!$localUser) {
                // Fallback: get from Supabase
                $token = $request->bearerToken();
                $supabaseUser = $this->supabaseAuth->getUser($token);
                
                // Try to find in local database
                if ($supabaseUser && isset($supabaseUser->email)) {
                    $localUser = \App\Models\User::where('email', $supabaseUser->email)->first();
                }
                
                if ($localUser) {
                    $userData = array_merge((array)$supabaseUser, [
                        'role' => $localUser->role,
                        'phone' => $localUser->phone,
                        'address' => $localUser->address,
                        'name' => $localUser->name
                    ]);
                } else {
                    $userData = $supabaseUser;
                }
            } else {
                // Use local user data
                $userData = [
                    'id' => $localUser->supabase_id ?? $localUser->id,
                    'email' => $localUser->email,
                    'name' => $localUser->name,
                    'role' => $localUser->role,
                    'phone' => $localUser->phone,
                    'address' => $localUser->address,
                    'created_at' => $localUser->created_at
                ];
            }
            
            return Response::json([
                'success' => true,
                'message' => 'Profile Retrieved',
                'data' => [
                    'user' => $userData,
                    'role' => $localUser ? $localUser->role : 'user' // Explicitly add role for clarity
                ]
            ], 200);
        } catch (Exception $e) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return Response::json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update local user data
            $user = \App\Models\User::where('email', $request->user()->email)->first();
            
            if ($user) {
                $user->update($request->only(['name', 'phone', 'address']));
            }
            
            return Response::json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => ['user' => $user]
            ], 200);
        } catch (Exception $e) {
            return Response::json([
                'success' => false,
                'message' => 'Error updating profile: ' . $e->getMessage()
            ], 500);
        }
    }
}
