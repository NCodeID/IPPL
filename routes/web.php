<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::view("/", "welcome");

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'webLogin']);
Route::post('/logout', [LoginController::class, 'webLogout'])->middleware('auth')->name('logout');
Route::get('/dashboard', [LoginController::class, 'showDashboard'])->middleware('auth')->name('dashboard');
