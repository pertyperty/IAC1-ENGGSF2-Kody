<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\RegisterAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RegisterAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(Request $request): View
    {
        return view('account.register', ['fromTower' => $request->query('from') === 'tower']);
    }

    public function store(RegisterAccountRequest $request, RegisterAccount $register): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $register->handle($data);

        $message = 'Your account was created. Check your email for a verification code and link. Delivery may take a moment.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return redirect()->route('verification.notice')->with('status', $message)->withInput(['email' => $data['email']]);
    }
}
