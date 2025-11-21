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

            $request->merge([
                "supabase_user_id" => $decoded->sub,
                "supabase_user_email" => $decoded->email ?? null,
            ]);

            return $next($request);
        } catch (Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized | Token tidak valid",
                ],
                401,
            );
        }
    }
}
