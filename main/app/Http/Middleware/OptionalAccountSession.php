<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalAccountSession
{
    public function handle(Request $request, Closure $next): Response
    {
        return $request->user() === null ? $next($request) : app(EnsureActiveAccountSession::class)->handle($request, $next);
    }
}
