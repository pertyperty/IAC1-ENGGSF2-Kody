<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ArchiveAccountRequest;
use App\Services\Account\AccountArchival;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AccountArchivalController extends Controller
{
    public function confirm(Request $request): Response
    {
        Gate::authorize('archive', $request->user());
        $version = $request->user()->profile_version;

        return response()->view('account.archive', compact('version'))->header('Cache-Control', 'no-store, private');
    }

    public function store(ArchiveAccountRequest $request, AccountArchival $accounts): RedirectResponse
    {
        $accounts->archive($request->user(), $request->session()->getId(), $request->validated());
        Auth::guard()->forgetUser();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account is archived and signed-in sessions have ended. Use account recovery to return when you are ready.');
    }
}
