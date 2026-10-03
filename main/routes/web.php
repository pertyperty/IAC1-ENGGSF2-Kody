<?php

use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\LoginController;
use App\Http\Controllers\Account\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:30,1,login:')->name('login.store');
    Route::get('/login/confirmation', [LoginController::class, 'confirmation'])->name('login.confirmation');
    Route::post('/login/confirmation', [LoginController::class, 'confirm'])->middleware('throttle:10,1,login-confirm:')->name('login.confirm');
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:6,1')->name('register.store');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-link', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.resend');
});
Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->middleware('throttle:30,1')->name('verification.verify');
Route::view('/dashboard', 'account.dashboard')->middleware(['auth', 'account.session'])->name('dashboard');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
