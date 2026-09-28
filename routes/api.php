<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/payments', [PaymentController::class, 'store']);
    Route::get('/sales/today', [SalesController::class, 'todaySales'])
        ->name('admin.sales.today');
    Route::get('/sales/trend', [SalesController::class, 'salesTrend'])
        ->name('admin.sales.trend');
});

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/webhooks/midtrans', MidtransWebhookController::class);

Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('products', ProductController::class)->only(['index', 'show']);
Route::apiResource('tables', TableController::class)->only(['index', 'show']);
Route::get('/transactions/today', [TransactionController::class, 'todayTransactions'])
    ->name('admin.transactions.today');
Route::prefix('reports')->group(function () {
    Route::get('/best-sellers', [ReportController::class, 'bestSellingItems'])
        ->name('admin.reports.best-sellers');

    Route::get('/recent-orders', [ReportController::class, 'recentOrders'])
        ->name('admin.reports.recent-orders');
});
