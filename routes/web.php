<?php

use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

// Public registration
Route::get('/register', [RegistrationController::class, 'show'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:5,10')->name('register.store');
Route::get('/register/lang/{locale}', [RegistrationController::class, 'switchLang'])->name('register.lang');

// Redirect root to register
Route::get('/', fn() => redirect()->route('register'));

// Admin auth
Route::get('/admin/login', [AdminController::class, 'loginForm'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.post');

// Protected admin routes
Route::middleware('admin')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/registrations', [AdminController::class, 'registrations'])->name('admin.registrations');

    Route::get('/admin/expenses', [ExpenseController::class, 'index'])->name('admin.expenses');
    Route::post('/admin/expenses', [ExpenseController::class, 'store'])->name('admin.expenses.store');
    Route::put('/admin/expenses/{id}', [ExpenseController::class, 'update'])->name('admin.expenses.update');
    Route::delete('/admin/expenses/{id}', [ExpenseController::class, 'destroy'])->name('admin.expenses.destroy');

    Route::get('/admin/categories', [CategoryController::class, 'index'])->name('admin.categories');
    Route::post('/admin/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::put('/admin/categories/{id}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/admin/categories/{id}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    Route::get('/admin/vendors', [VendorController::class, 'index'])->name('admin.vendors');
    Route::post('/admin/vendors', [VendorController::class, 'store'])->name('admin.vendors.store');
    Route::put('/admin/vendors/{id}', [VendorController::class, 'update'])->name('admin.vendors.update');
    Route::delete('/admin/vendors/{id}', [VendorController::class, 'destroy'])->name('admin.vendors.destroy');

    Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
});
