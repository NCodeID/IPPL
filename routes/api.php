<?php

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
        Route::post('/orders', [OrderController::class, 'store']);
    },
);
