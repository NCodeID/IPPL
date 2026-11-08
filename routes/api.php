<?php

use App\Http\Controllers\Akuntan\BestSellerController;
use App\Http\Controllers\Akuntan\PurchaseRequestController;
use App\Http\Controllers\Akuntan\SalesReportController;
use App\Http\Controllers\Akuntan\SettlementController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Rbac\PermissionController;
use App\Http\Controllers\Rbac\RoleController;
use App\Http\Controllers\Tables\TableController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('role.permissions');
    });

    Route::post('/logout', [LogoutController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'permission:manage_roles'])->group(
    function () {
        Route::apiResource('roles', RoleController::class);
    },
);

Route::middleware(['auth:sanctum', 'permission:manage_permissions'])->group(
    function () {
        Route::apiResource('permissions', PermissionController::class);
    },
);

Route::middleware(['auth:sanctum', 'permission:manage_users'])->group(
    function () {
        Route::post('/register', [RegisterController::class, 'register']);
    },
);

Route::middleware(['auth:sanctum', 'active', 'permission:view_tables'])->group(
    function () {
        Route::get('/tables/summary', [TableController::class, 'summary']);
        Route::get('/tables', [TableController::class, 'index']);
        Route::get('/tables/{table}', [TableController::class, 'show']);
    },
);

Route::middleware(['auth:sanctum', 'active', 'permission:manage_orders'])->group(
    function () {
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/orders/monitor', [OrderController::class, 'monitor']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store']);
    },
);

Route::middleware(['auth:sanctum', 'active', 'permission:manage_purchase_requests'])->group(
    function () {
        Route::get('/purchase-requests', [PurchaseRequestController::class, 'index']);
        Route::get('/purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'show']);
        Route::post('/purchase-requests/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve']);
        Route::post('/purchase-requests/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject']);

        Route::get('/settlements', [SettlementController::class, 'index']);
        Route::get('/settlements/{purchaseRequest}', [SettlementController::class, 'show']);
        Route::post('/settlements/{purchaseRequest}/complete', [SettlementController::class, 'complete']);
        Route::post('/settlements/{purchaseRequest}/revision', [SettlementController::class, 'requestRevision']);
    },
);

Route::middleware(['auth:sanctum', 'active', 'permission:manage_payments'])->group(
    function () {
        Route::get('/sales-reports', [SalesReportController::class, 'index']);
        Route::get('/sales-reports/{order}', [SalesReportController::class, 'show']);
    },
);

Route::middleware(['auth:sanctum', 'active', 'permission:manage_orders'])->group(
    function () {
        Route::get('/best-sellers', [BestSellerController::class, 'index']);
        Route::get('/best-sellers/{product}', [BestSellerController::class, 'show']);
    },
);
