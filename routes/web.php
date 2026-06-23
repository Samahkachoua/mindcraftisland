<?php

use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

// Public registration
Route::get('/register', [RegistrationController::class, 'show'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');
Route::get('/register/lang/{locale}', [RegistrationController::class, 'switchLang'])->name('register.lang');

// Redirect root to register
Route::get('/', fn() => redirect()->route('register'));

// Admin auth
Route::get('/admin/login', [AdminController::class, 'loginForm'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.post');

// Protected admin routes
Route::middleware('admin')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
});
