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
            'email'=> 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return Response::json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            $userData = $this->supabaseAuth->register(
                $request->email,
                $request->password,
                ['name' => $request->name]
            );

            $responseData = [];
            if (isset($userData->user)) {
                $responseData['user'] = $userData->user;
            }
            if (isset($userData->id)) {
                $responseData['id'] = $userData->id;
            }

            return Response::json([
                'success' => true,
                'message' => 'Registration Successful. Please check your email for confirmation.',
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
            
            return Response::json([
                'success' => true,
                'message' => 'Login Successful',
                'data' => [
                    'user' => $authData->user,
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
            $token = $request->bearerToken();
            $user = $this->supabaseAuth->getUser($token);
            
            return Response::json([
                'success' => true,
                'message' => 'Profile Retrieved',
                'data' => ['user' => $user]
            ], 200);
        } catch (Exception $e) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
