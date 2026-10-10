<?php

use App\Enums\Role;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Transactions\PaymentController;
use App\Http\Middleware\EnsureActiveAccountSession;
use App\Http\Middleware\GoogleCallbackPrivacy;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function (): void {
            // Probes must not depend on database-backed sessions or CSRF state.
            Route::post('/webhooks/xendit', [PaymentController::class, 'webhook'])->middleware(ThrottleRequests::class.':600,1')->name('xendit.webhook');
            Route::get('/up', [HealthController::class, 'live'])->name('health.live');
            Route::get('/ready', [HealthController::class, 'ready'])->name('health.ready');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['account.session' => EnsureActiveAccountSession::class]);
        $middleware->prependToPriorityList(ThrottleRequests::class, GoogleCallbackPrivacy::class);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
        $middleware->trustProxies(at: '*'); // Remove later
        // Test-case whitespace is part of the expected program behavior.
        $middleware->trimStrings(except: ['test_cases.*.input', 'test_cases.*.expected_output', 'source_code']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['verification_token', 'recovery_token', 'source_code', 'code', 'state', 'access_token', 'id_token', 'client_secret', 'code_verifier', 'mobile', 'street', 'city', 'province', 'postal_code', 'account_holder_name']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
