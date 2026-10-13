<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Account\RoleChangeNotices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleUpdateController extends Controller
{
    public function show(Request $request, RoleChangeNotices $notices): JsonResponse
    {
        return response()->json($notices->snapshot($request->user(), $request->session()->getId()))->header('Cache-Control', 'no-store, private');
    }

    public function acknowledge(Request $request, string $notification, RoleChangeNotices $notices): JsonResponse|RedirectResponse
    {
        $destination = $notices->acknowledge($request->user(), $request->session()->getId(), $notification);

        return $request->expectsJson() ? response()->json(['redirect' => $destination, 'overview' => route('dashboard')])
            : redirect($destination)->with('status', 'Your new role is ready. Welcome to your creator workspace.');
    }
}
