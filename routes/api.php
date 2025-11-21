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
    Route::put('/profile', [AuthSupabaseController::class, 'updateProfile']);
    
    // Books - Public read, staff/admin write
    Route::get('/books', [\App\Http\Controllers\Api\BookController::class, 'index']);
    Route::get('/books/{id}', [\App\Http\Controllers\Api\BookController::class, 'show']);
    Route::middleware('role:staff,admin')->group(function () {
        Route::post('/books', [\App\Http\Controllers\Api\BookController::class, 'store']);
        Route::put('/books/{id}', [\App\Http\Controllers\Api\BookController::class, 'update']);
        Route::delete('/books/{id}', [\App\Http\Controllers\Api\BookController::class, 'destroy']);
    });
    
    // Borrowings
    Route::get('/borrowings', [\App\Http\Controllers\Api\BorrowingController::class, 'index']);
    Route::post('/borrowings', [\App\Http\Controllers\Api\BorrowingController::class, 'store']);
    Route::get('/borrowings/my', [\App\Http\Controllers\Api\BorrowingController::class, 'myBorrowings']);
    Route::get('/borrowings/{id}', [\App\Http\Controllers\Api\BorrowingController::class, 'show']);
    Route::post('/borrowings/{id}/return', [\App\Http\Controllers\Api\BorrowingController::class, 'returnBook']);
    
    // Promotions - Public read, staff/admin write
    Route::get('/promotions', [\App\Http\Controllers\Api\PromotionController::class, 'index']);
    Route::get('/promotions/{id}', [\App\Http\Controllers\Api\PromotionController::class, 'show']);
    Route::middleware('role:staff,admin')->group(function () {
        Route::post('/promotions', [\App\Http\Controllers\Api\PromotionController::class, 'store']);
        Route::put('/promotions/{id}', [\App\Http\Controllers\Api\PromotionController::class, 'update']);
        Route::delete('/promotions/{id}', [\App\Http\Controllers\Api\PromotionController::class, 'destroy']);
    });
    
    // Users Management (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
        Route::get('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'show']);
        Route::post('/users', [\App\Http\Controllers\Api\UserController::class, 'store']);
        Route::put('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'update']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'destroy']);
    });
    
    // Reports (Staff/Admin only)
    Route::middleware('role:staff,admin')->group(function () {
        Route::get('/reports/borrowings', [\App\Http\Controllers\Api\ReportController::class, 'borrowings']);
        Route::middleware('role:admin')->get('/reports/export', [\App\Http\Controllers\Api\ReportController::class, 'export']);
    });
});

// Auto-return endpoint (untuk scheduler, tidak perlu auth)
Route::get('/borrowings/auto-return', [\App\Http\Controllers\Api\BorrowingController::class, 'autoReturn']);
