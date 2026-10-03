<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\DeleteAccountRequest;
use App\Services\Account\AccountDeletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AccountDeletionController extends Controller
{
    public function confirm(Request $request, AccountDeletion $accounts): Response
    {
        Gate::authorize('delete', $request->user());
        $version = $request->user()->profile_version;
        $authored = $accounts->hasAuthoredContent($request->user());

        return response()->view('account.delete', compact('version', 'authored'))->header('Cache-Control', 'no-store, private');
    }

    public function store(DeleteAccountRequest $request, AccountDeletion $accounts): RedirectResponse
    {
        $accounts->delete($request->user(), $request->session()->getId(), $request->validated());
        Auth::guard()->forgetUser();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account is deleted and cannot be recovered. Private credential files, if any, are queued for permanent removal.');
    }
}
