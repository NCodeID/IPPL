<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin,runner')->group(function () {
        Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
        Route::apiResource('tables', TableController::class)->only(['index', 'show']);
    });
    
    Route::middleware('role:admin,kasir,runner,gudang,akuntan')->group(function () {
        Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('tables', TableController::class)->except(['index', 'show']);
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        Route::apiResource('recipes', RecipeController::class);
        Route::apiResource('users', UserController::class);
    });

    Route::middleware('role:admin,akuntan')->group(function () {
        Route::get('/orders/history', [OrderController::class, 'history']);
    });
    
    Route::middleware('role:admin,kasir,runner,dapur')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
    });

    Route::middleware('role:admin,runner,dapur')->group(function () {
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    });

    Route::middleware('role:admin,runner')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
        Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    });

    Route::middleware('role:admin,kasir')->group(function () {
        Route::post('/orders/{order}/payments', [PaymentController::class, 'store']);
    });

    Route::middleware('role:admin,akuntan,gudang')->group(function () {
        Route::get('/purchase-requests', [PurchaseRequestController::class, 'index']);
        Route::get('/purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'show']);
    });
    
    Route::middleware('role:admin,gudang')->group(function () {
        Route::post('/purchase-requests', [PurchaseRequestController::class, 'store']);
        Route::post('/purchase-requests/{purchaseRequest}/receive', [PurchaseRequestController::class, 'receive']);
    });
    
    Route::middleware('role:admin,akuntan')->group(function () {
        Route::patch('/purchase-requests/{purchaseRequest}/review', [PurchaseRequestController::class, 'review']);
        Route::patch('/purchase-requests/{purchaseRequest}/settle', [PurchaseRequestController::class, 'settle']);
    });

    Route::middleware('role:admin,akuntan')->group(function () {
        Route::get('/sales/today', [SalesController::class, 'todaySales']);
        Route::get('/sales/trend', [SalesController::class, 'salesTrend']);
        Route::get('/transactions/today', [TransactionController::class, 'todayTransactions']);
        Route::prefix('reports')->group(function () {
            Route::get('/best-sellers', [ReportController::class, 'bestSellingItems']);
            Route::get('/recent-orders', [ReportController::class, 'recentOrders']);
        });
    });
});

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/webhooks/midtrans', MidtransWebhookController::class);