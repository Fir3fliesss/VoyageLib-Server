<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class SupabaseJwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized | Token tidak ditemukan",
                ],
                401,
            );
        }

        try {
            $jwtSecret = config("services.supabase.jwt_secret");
            $decoded = JWT::decode($token, new Key($jwtSecret, "HS256"));

            // Get or create user in local database
            $email = $decoded->email ?? null;
            $supabaseId = $decoded->sub;
            
            if ($email) {
                $user = \App\Models\User::where('email', $email)
                    ->orWhere('supabase_id', $supabaseId)
                    ->first();
                
                if (!$user) {
                    // Create user in local database if not exists
                    $user = \App\Models\User::create([
                        'name' => $decoded->user_metadata->name ?? $email,
                        'email' => $email,
                        'supabase_id' => $supabaseId,
                        'password' => bcrypt(uniqid()), // Random password since we use Supabase auth
                        'role' => $decoded->user_metadata->role ?? 'user'
                    ]);
                } else {
                    // Update supabase_id if not set
                    if (!$user->supabase_id) {
                        $user->update(['supabase_id' => $supabaseId]);
                    }
                }
                
                // Set user in request
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });
            }

            $request->merge([
                "supabase_user_id" => $decoded->sub,
                "supabase_user_email" => $decoded->email ?? null,
            ]);

            return $next($request);
        } catch (Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized | Token tidak valid: " . $e->getMessage(),
                ],
                401,
            );
        }
    }
}
