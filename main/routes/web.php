<?php

use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\InstructorReviewController;
use App\Http\Controllers\Account\LoginController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\RecoveryController;
use App\Http\Controllers\Account\RegistrationController;
use App\Http\Controllers\Content\ModuleReviewController;
use App\Http\Controllers\Content\ModuleStudioController;
use App\Http\Controllers\Content\PublishedModuleController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlayController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LearningController::class, 'home'])->name('home');
Route::get('/learn', [LearningController::class, 'catalog'])->name('learning.catalog');
Route::middleware(['auth', 'account.session'])->group(function (): void {
    Route::get('/updates', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/updates/{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification')->middleware('throttle:30,1,notification-read:')->name('notifications.read');
    Route::get('/learn/modules/{module}', [PublishedModuleController::class, 'show'])->name('modules.show');
    Route::post('/learn/modules/{module}/revisions/{revision}/game', [PublishedModuleController::class, 'game'])->whereNumber('revision')->middleware('throttle:20,1,module-game:')->name('modules.game');
    Route::post('/learn/modules/{module}/revisions/{revision}/quiz', [PublishedModuleController::class, 'quiz'])->whereNumber('revision')->middleware('throttle:20,1,module-quiz:')->name('modules.quiz');
    Route::get('/create', [ModuleStudioController::class, 'index'])->name('studio.index');
    Route::get('/create/modules/new', [ModuleStudioController::class, 'create'])->name('studio.create');
    Route::post('/create/modules', [ModuleStudioController::class, 'store'])->middleware('throttle:10,1,module-create:')->name('studio.store');
    Route::get('/create/modules/{module}', [ModuleStudioController::class, 'edit'])->name('studio.edit');
    Route::put('/create/modules/{module}', [ModuleStudioController::class, 'update'])->middleware('throttle:20,1,module-edit:')->name('studio.update');
    Route::post('/create/modules/{module}/submit', [ModuleStudioController::class, 'submit'])->middleware('throttle:10,1,module-submit:')->name('studio.submit');
    Route::get('/create/modules/{module}/archive', [ModuleStudioController::class, 'archiveConfirmation'])->name('studio.archive-confirmation');
    Route::post('/create/modules/{module}/archive', [ModuleStudioController::class, 'archive'])->middleware('throttle:10,1,module-archive:')->name('studio.archive');
    Route::get('/manage/modules', [ModuleReviewController::class, 'index'])->name('module-reviews.index');
    Route::get('/manage/modules/{module}', [ModuleReviewController::class, 'show'])->name('module-reviews.show');
    Route::post('/manage/modules/{module}', [ModuleReviewController::class, 'review'])->middleware('throttle:10,1,module-review:')->name('module-reviews.review');
});
Route::get('/learn/{module}', [LearningController::class, 'show'])->middleware(['auth', 'account.session'])->name('learning.show');

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
Route::get('/dashboard', [PlayController::class, 'hub'])->middleware(['auth', 'account.session'])->name('dashboard');
Route::post('/play/{level}/game', [PlayController::class, 'game'])->middleware(['auth', 'account.session', 'throttle:20,1,play-game:'])->name('play.game');
Route::post('/play/{level}/quiz', [PlayController::class, 'quiz'])->middleware(['auth', 'account.session', 'throttle:20,1,play-quiz:'])->name('play.quiz');
Route::get('/account', ProfileController::class)->middleware(['auth', 'account.session'])->name('account.show');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'account.session'])->prefix('manage/instructors')->name('instructor-reviews.')->group(function (): void {
    Route::get('/', [InstructorReviewController::class, 'index'])->name('index');
    Route::get('/{application}', [InstructorReviewController::class, 'show'])->name('show');
    Route::get('/{application}/credential', [InstructorReviewController::class, 'credential'])->middleware('throttle:20,1,credential-read:')->name('credential');
    Route::post('/{application}', [InstructorReviewController::class, 'review'])->middleware('throttle:10,1,creator-review:')->name('review');
});
