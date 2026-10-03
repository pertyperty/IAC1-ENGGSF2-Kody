<?php

use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\LoginController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\RecoveryController;
use App\Http\Controllers\Account\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/recover', [RecoveryController::class, 'create'])->name('recovery.request');
    Route::post('/recover', [RecoveryController::class, 'request'])->middleware('throttle:6,1,recovery-email:')->name('recovery.send');
    Route::post('/recover/authorize', [RecoveryController::class, 'authorizeToken'])->middleware('throttle:20,1,recovery-token:')->name('recovery.authorize');
    Route::get('/recover/reset', [RecoveryController::class, 'resetForm'])->name('recovery.reset');
    Route::post('/recover/reset', [RecoveryController::class, 'complete'])->middleware('throttle:10,1,recovery-reset:')->name('recovery.complete');
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
Route::get('/account', ProfileController::class)->middleware(['auth', 'account.session'])->name('account.show');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
