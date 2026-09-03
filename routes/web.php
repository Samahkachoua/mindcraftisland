<?php

use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\RentalItemController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\AccountController;
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

    Route::get('/admin/programs', [ProgramController::class, 'index'])->name('admin.programs');
    Route::post('/admin/programs', [ProgramController::class, 'store'])->name('admin.programs.store');
    Route::put('/admin/programs/{id}', [ProgramController::class, 'update'])->name('admin.programs.update');
    Route::delete('/admin/programs/{id}', [ProgramController::class, 'destroy'])->name('admin.programs.destroy');

    Route::get('/admin/sessions', [SessionController::class, 'index'])->name('admin.sessions');
    Route::post('/admin/sessions', [SessionController::class, 'store'])->name('admin.sessions.store');
    Route::put('/admin/sessions/{id}', [SessionController::class, 'update'])->name('admin.sessions.update');
    Route::delete('/admin/sessions/{id}', [SessionController::class, 'destroy'])->name('admin.sessions.destroy');

    Route::get('/admin/enrollments', [EnrollmentController::class, 'index'])->name('admin.enrollments');
    Route::post('/admin/enrollments', [EnrollmentController::class, 'store'])->name('admin.enrollments.store');
    Route::put('/admin/enrollments/{id}', [EnrollmentController::class, 'update'])->name('admin.enrollments.update');
    Route::delete('/admin/enrollments/{id}', [EnrollmentController::class, 'destroy'])->name('admin.enrollments.destroy');

    Route::get('/admin/payments', [PaymentController::class, 'index'])->name('admin.payments');
    Route::post('/admin/payments', [PaymentController::class, 'store'])->name('admin.payments.store');
    Route::delete('/admin/payments/{id}', [PaymentController::class, 'destroy'])->name('admin.payments.destroy');

    Route::get('/admin/members', [MemberController::class, 'index'])->name('admin.members');
    Route::post('/admin/members', [MemberController::class, 'store'])->name('admin.members.store');
    Route::put('/admin/members/{id}', [MemberController::class, 'update'])->name('admin.members.update');
    Route::delete('/admin/members/{id}', [MemberController::class, 'destroy'])->name('admin.members.destroy');


    Route::get('/admin/rental-items', [RentalItemController::class, 'index'])->name('admin.rental-items');
    Route::post('/admin/rental-items', [RentalItemController::class, 'store'])->name('admin.rental-items.store');
    Route::put('/admin/rental-items/{id}', [RentalItemController::class, 'update'])->name('admin.rental-items.update');
    Route::delete('/admin/rental-items/{id}', [RentalItemController::class, 'destroy'])->name('admin.rental-items.destroy');

    Route::get('/admin/rentals', [RentalController::class, 'index'])->name('admin.rentals');
    Route::post('/admin/rentals', [RentalController::class, 'store'])->name('admin.rentals.store');
    Route::post('/admin/rentals/{id}/return', [RentalController::class, 'returnRental'])->name('admin.rentals.return');
    Route::put('/admin/rentals/{id}', [RentalController::class, 'update'])->name('admin.rentals.update');
    Route::delete('/admin/rentals/{id}', [RentalController::class, 'destroy'])->name('admin.rentals.destroy');

    Route::get('/admin/accounts', [AccountController::class, 'index'])->name('admin.accounts');
    Route::post('/admin/accounts', [AccountController::class, 'store'])->name('admin.accounts.store');
    Route::get('/admin/accounts/{id}', [AccountController::class, 'show'])->name('admin.accounts.show');
    Route::put('/admin/accounts/{id}', [AccountController::class, 'update'])->name('admin.accounts.update');
    Route::delete('/admin/accounts/{id}', [AccountController::class, 'destroy'])->name('admin.accounts.destroy');

    Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
});
