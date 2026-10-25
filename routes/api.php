<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - SILAGO
|--------------------------------------------------------------------------
| Semua route API menggunakan prefix /api/v1 dan autentikasi Sanctum.
| Middleware:
|   - maintenance: blokir saat mode maintenance (kecuali app-config)
|   - auth:sanctum: wajib login
|   - user.active: tolak pengguna suspended
*/

// ─── Publik: tidak perlu login ─────────────────────────────────────────────
Route::prefix('v1')->middleware('maintenance')->group(function () {
    Route::get('/app-config', [\App\Http\Controllers\Api\V1\ConfigController::class, 'index']);

    // Autentikasi
    Route::prefix('auth')->group(function () {
        Route::post('/register',             [\App\Http\Controllers\Api\V1\AuthController::class, 'register']);
        Route::post('/verify-email',         [\App\Http\Controllers\Api\V1\AuthController::class, 'verifyEmail']);
        Route::post('/resend-verification',  [\App\Http\Controllers\Api\V1\AuthController::class, 'resendVerification']);
        Route::post('/login',                [\App\Http\Controllers\Api\V1\AuthController::class, 'login'])->withoutMiddleware('maintenance');
        Route::post('/verify-otp',           [\App\Http\Controllers\Api\V1\AuthController::class, 'verifyOtp']);
        Route::post('/resend-otp',           [\App\Http\Controllers\Api\V1\AuthController::class, 'resendOtp']);
        Route::post('/login-google',         [\App\Http\Controllers\Api\V1\AuthController::class, 'loginGoogle']);
        Route::post('/forgot-password',      [\App\Http\Controllers\Api\V1\AuthController::class, 'forgotPassword']);
        Route::post('/reset-password',       [\App\Http\Controllers\Api\V1\AuthController::class, 'resetPassword']);
    });

    // Kategori publik
    Route::get('/categories', [\App\Http\Controllers\Api\V1\CategoryController::class, 'index']);

    // ─── Butuh login + pengguna aktif ────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'user.active'])->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);

        // Profil sendiri
        Route::get('/me',             [\App\Http\Controllers\Api\V1\ProfileController::class, 'me']);
        Route::put('/me',             [\App\Http\Controllers\Api\V1\ProfileController::class, 'update']);
        Route::post('/me/fcm-token',  [\App\Http\Controllers\Api\V1\ProfileController::class, 'updateFcmToken']);
        // Route::post('/me/2fa/toggle', [\App\Http\Controllers\Api\V1\AuthController::class, 'toggleTwoFactor']); // 2FA disabled
        Route::get('/me/summary',     [\App\Http\Controllers\Api\V1\ProfileController::class, 'summary']);
        Route::get('/me/products',    [\App\Http\Controllers\Api\V1\ProductController::class, 'myProducts']);
        Route::get('/me/favorites',   [\App\Http\Controllers\Api\V1\FavoriteController::class, 'index']);

        // Katalog
        Route::get('/products',               [\App\Http\Controllers\Api\V1\ProductController::class, 'index']);
        Route::post('/products',              [\App\Http\Controllers\Api\V1\ProductController::class, 'store']);
        Route::get('/products/{product}',     [\App\Http\Controllers\Api\V1\ProductController::class, 'show']);
        Route::put('/products/{product}',     [\App\Http\Controllers\Api\V1\ProductController::class, 'update']);
        Route::delete('/products/{product}',  [\App\Http\Controllers\Api\V1\ProductController::class, 'destroy']);
        Route::post('/products/{product}/renew',    [\App\Http\Controllers\Api\V1\ProductController::class, 'renew']);
        Route::post('/products/{product}/favorite', [\App\Http\Controllers\Api\V1\FavoriteController::class, 'toggle']);
        Route::post('/products/{product}/report',   [\App\Http\Controllers\Api\V1\ReportController::class, 'reportProduct']);

        // Titik COD
        Route::get('/cod-points', [\App\Http\Controllers\Api\V1\CodPointController::class, 'index']);

        // Chat dan negosiasi
        Route::get('/conversations',                              [\App\Http\Controllers\Api\V1\ConversationController::class, 'index']);
        Route::get('/conversations/unread-count',                 [\App\Http\Controllers\Api\V1\ConversationController::class, 'unreadCount']);
        Route::post('/conversations/read-all',                     [\App\Http\Controllers\Api\V1\ConversationController::class, 'markAllRead']);
        Route::post('/products/{product}/conversations',          [\App\Http\Controllers\Api\V1\ConversationController::class, 'store']);
        Route::get('/conversations/{conversation}',              [\App\Http\Controllers\Api\V1\ConversationController::class, 'show']);
        Route::get('/conversations/{conversation}/messages',     [\App\Http\Controllers\Api\V1\MessageController::class, 'index']);
        Route::post('/conversations/{conversation}/messages',    [\App\Http\Controllers\Api\V1\MessageController::class, 'store']);
        Route::post('/conversations/{conversation}/read',        [\App\Http\Controllers\Api\V1\MessageController::class, 'markRead']);
        Route::post('/conversations/{conversation}/offers',      [\App\Http\Controllers\Api\V1\OfferController::class, 'store']);

        // Penawaran
        Route::post('/offers/{offer}/accept', [\App\Http\Controllers\Api\V1\OfferController::class, 'accept']);
        Route::post('/offers/{offer}/reject', [\App\Http\Controllers\Api\V1\OfferController::class, 'reject']);
        Route::post('/offers/{offer}/withdraw', [\App\Http\Controllers\Api\V1\OfferController::class, 'withdraw']);

        // Transaksi
        Route::get('/deals',                      [\App\Http\Controllers\Api\V1\DealController::class, 'index']);
        Route::get('/deals/{deal}',               [\App\Http\Controllers\Api\V1\DealController::class, 'show']);
        Route::post('/deals/{deal}/qr',           [\App\Http\Controllers\Api\V1\DealController::class, 'generateQr']);
        Route::post('/deals/{deal}/complete',     [\App\Http\Controllers\Api\V1\DealController::class, 'complete']);
        Route::post('/deals/{deal}/cancel',       [\App\Http\Controllers\Api\V1\DealController::class, 'cancel']);
        Route::post('/deals/{deal}/review',       [\App\Http\Controllers\Api\V1\ReviewController::class, 'store']);
        Route::post('/deals/{deal}/report',       [\App\Http\Controllers\Api\V1\ReportController::class, 'reportDeal']);

        // Profil publik
        Route::get('/users/{user}',           [\App\Http\Controllers\Api\V1\UserController::class, 'show']);
        Route::get('/users/{user}/reviews',   [\App\Http\Controllers\Api\V1\UserController::class, 'reviews']);
        Route::get('/users/{user}/products',  [\App\Http\Controllers\Api\V1\UserController::class, 'products']);
        Route::post('/users/{user}/report',   [\App\Http\Controllers\Api\V1\ReportController::class, 'reportUser']);

        // Notifikasi
        Route::get('/notifications',                       [\App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
        Route::get('/notifications/unread-count',          [\App\Http\Controllers\Api\V1\NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{notification}/read',  [\App\Http\Controllers\Api\V1\NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all',             [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAllRead']);
    });
});
