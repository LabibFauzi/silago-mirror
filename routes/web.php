<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataManagerController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CodPointController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\SettingController;

Route::redirect('/', '/admin/login');

Route::prefix('admin')->name('admin.')->group(function() {
    // Guest
    Route::get('/login', [AdminAuthController::class, 'login'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'authenticate'])->name('authenticate');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    
    // Authenticated admin
    Route::middleware(['auth'])->group(function() { // Assuming you have 'admin' middleware separately or use auth for simplicity for now
        Route::get('/', function() { return redirect()->route('admin.dashboard'); });
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        Route::get('/verification', [VerificationController::class, 'index'])->name('verification.index');
        Route::post('/verification/{product}/approve', [VerificationController::class, 'approve'])->name('verification.approve');
        Route::post('/verification/{product}/reject', [VerificationController::class, 'reject'])->name('verification.reject');
        
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/{report}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
        Route::post('/reports/{report}/dismiss', [ReportController::class, 'dismiss'])->name('reports.dismiss');
        
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/{deal}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::post('/transactions/{deal}/support-buyer', [TransactionController::class, 'supportBuyer'])->name('transactions.supportBuyer');
        Route::post('/transactions/{deal}/support-seller', [TransactionController::class, 'supportSeller'])->name('transactions.supportSeller');
        
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('/categories/{category}/products', [CategoryController::class, 'products'])->name('categories.products');
        
        Route::get('/cod-points', [CodPointController::class, 'index'])->name('cod-points.index');
        Route::post('/cod-points', [CodPointController::class, 'store'])->name('cod-points.store');
        Route::put('/cod-points/{point}', [CodPointController::class, 'update'])->name('cod-points.update');
        
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::put('/settings', [SettingController::class, 'update']);

        // Data Manager — hanya saat maintenance mode aktif + admin only
        Route::middleware(['maintenance.active'])->prefix('data-manager')->name('data-manager.')->group(function () {
            Route::get('/', [DataManagerController::class, 'index'])->name('index');
            Route::get('/{table}', [DataManagerController::class, 'table'])->name('table');
            Route::get('/{table}/{id}/json', [DataManagerController::class, 'show'])->name('show');
            Route::post('/{table}', [DataManagerController::class, 'store'])->name('store');
            Route::put('/{table}/{id}', [DataManagerController::class, 'update'])->name('update');
            Route::delete('/{table}/{id}', [DataManagerController::class, 'destroy'])->name('destroy');
        });
    });
});
