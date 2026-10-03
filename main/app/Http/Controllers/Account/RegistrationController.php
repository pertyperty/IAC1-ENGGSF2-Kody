<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\RegisterAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RegisterAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('account.register');
    }

    public function store(RegisterAccountRequest $request, RegisterAccount $register): JsonResponse|RedirectResponse
    {
        $register->handle($request->validated(), $request->file('credential_document'));

        $message = 'Your account was created. Check your email for a verification link. Delivery may take a moment; you can request another link later.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return redirect()->route('verification.notice')->with('status', $message);
    }
}
