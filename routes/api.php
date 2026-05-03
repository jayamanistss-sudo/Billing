<?php

use App\Http\Controllers\Api\V1\Admin\SuperAdminAuthController;
use App\Http\Controllers\Api\V1\Admin\SuperAdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillController;
use App\Http\Controllers\Api\V1\CashfreeController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\StockMovementController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

// Super Admin routes
Route::prefix('v1/admin')->group(function () {
    Route::post('/login', [SuperAdminAuthController::class, 'login']);
    Route::middleware('super_admin')->group(function () {
        Route::post('/logout', [SuperAdminAuthController::class, 'logout']);
        Route::get('/me', [SuperAdminAuthController::class, 'me']);
        Route::get('/stats', [SuperAdminController::class, 'stats']);
        Route::get('/tenants', [SuperAdminController::class, 'tenants']);
        Route::get('/tenants/{id}', [SuperAdminController::class, 'showTenant']);
        Route::patch('/tenants/{id}/toggle', [SuperAdminController::class, 'toggleTenant']);
        Route::get('/plans', [SuperAdminController::class, 'plans']);
        Route::post('/plans', [SuperAdminController::class, 'storePlan']);
        Route::put('/plans/{id}', [SuperAdminController::class, 'updatePlan']);
        Route::delete('/plans/{id}', [SuperAdminController::class, 'deletePlan']);
        Route::get('/subscriptions', [SuperAdminController::class, 'subscriptions']);
    });
});

// Cashfree webhook (outside auth — Cashfree calls this directly)
Route::post('/v1/payments/webhook', [CashfreeController::class, 'webhook']);

Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/pin-login', [AuthController::class, 'pinLogin']);
    Route::get('/plans', [PlanController::class, 'index']);

    // Protected routes
    Route::middleware(['auth:api', 'tenant'])->group(function () {

        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Categories
        Route::apiResource('categories', CategoryController::class);
        Route::post('/categories/reorder', [CategoryController::class, 'reorder']);

        // Customers
        Route::get('/customers/search', [CustomerController::class, 'search']);
        Route::get('/customers/{customer}/ledger', [CustomerController::class, 'creditLedger']);
        Route::apiResource('customers', CustomerController::class);

        // Products — read accessible to all; writes restricted to owner/manager
        Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
        Route::post('/products/bulk-import', [ProductController::class, 'bulkImport'])
            ->middleware('plan:api_access');
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::middleware('role:owner,manager')->group(function () {
            Route::post('/products', [ProductController::class, 'store']);
            Route::put('/products/{product}', [ProductController::class, 'update']);
            Route::patch('/products/{product}', [ProductController::class, 'update']);
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        });

        // Bills
        Route::post('/bills/{bill}/return', [BillController::class, 'processReturn'])
            ->middleware('role:owner,manager');
        Route::apiResource('bills', BillController::class)->except(['update', 'destroy']);

        // Stock movements
        Route::get('/stock-movements', [StockMovementController::class, 'index']);
        Route::post('/stock-movements/adjust', [StockMovementController::class, 'adjust'])
            ->middleware('role:owner,manager');

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('/daily', [ReportController::class, 'daily']);
            Route::get('/monthly', [ReportController::class, 'monthly']);
            Route::get('/top-products', [ReportController::class, 'topProducts']);
            Route::get('/gst-summary', [ReportController::class, 'gstSummary']);
            Route::get('/profit', [ReportController::class, 'profit']);
        });

        // Owner-only routes
        Route::middleware('role:owner')->group(function () {
            Route::get('/tenant', [TenantController::class, 'show']);
            Route::put('/tenant', [TenantController::class, 'update']);
            Route::post('/tenant/logo', [TenantController::class, 'uploadLogo']);
            Route::get('/tenant/plan', [PlanController::class, 'current']);
            Route::post('/tenant/upgrade', [PlanController::class, 'upgrade']);
            Route::apiResource('users', UserController::class);

            // Cashfree payment routes
            Route::post('/payments/create-order', [CashfreeController::class, 'createOrder']);
            Route::get('/payments/verify', [CashfreeController::class, 'verifyPayment']);
        });
    });
});
