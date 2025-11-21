<?php

use App\Http\Controllers\Api\AuthSupabaseController;
use Illuminate\Support\Facades\Route;

//Public Routes
Route::prefix('auth')->group(function () {
    // Sanctum Auth Routes
    // Route::post('/register', [AuthController::class, 'register']);
    // Route::post('/login', [AuthController::class, 'login']);

    //Supabase Auth Routes
    Route::post('/register', [AuthSupabaseController::class, 'register']);
    Route::post('/login', [AuthSupabaseController::class, 'login']);
});

// Route::middleware('auth:sanctum')->group(function () {
    
//     Route::prefix('auth')->group(function () {
//         Route::get('/user', [AuthController::class, 'user']);
//         Route::post('/logout', [AuthController::class, 'logout']);
//     });

//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });
// });

Route::middleware('supabase.auth')->group(function(){
    Route::post('/logout', [AuthSupabaseController::class, 'logout']);
    Route::get('/profile', [AuthSupabaseController::class, 'profile']);
});
