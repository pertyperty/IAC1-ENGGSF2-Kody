<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Services\Account\ProfileEditing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProfileEditingController extends Controller
{
    public function edit(Request $request): Response
    {
        Gate::authorize('update', $request->user());
        $profile = $request->user()->only(['username', 'first_name', 'last_name', 'email', 'profile_version']);

        return response()->view('account.edit-profile', compact('profile'))->header('Cache-Control', 'no-store, private');
    }

    public function update(UpdateProfileRequest $request, ProfileEditing $profiles): RedirectResponse
    {
        $result = $profiles->update($request->user(), $request->session()->getId(), $request->validated());
        if (in_array($result, ['verify', 'sign-in'], true)) {
            Auth::guard()->forgetUser();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($result === 'verify' ? 'verification.notice' : 'login')->with('status',
                $result === 'verify' ? 'Profile saved. Check your new email to verify it before signing in.' : 'Password changed. Sign in again with your new password.');
        }

        return redirect()->route('account.show')->with('status', $result === 'unchanged' ? 'Your profile is already up to date.' : 'Your profile has been saved.');
    }
}
