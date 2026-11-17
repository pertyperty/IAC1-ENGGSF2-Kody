<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\DecideCreatorBadgeRequest;
use App\Models\User;
use App\Services\Administration\CreatorBadges;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreatorBadgeController extends Controller
{
    public function show(User $creator, CreatorBadges $badges): Response
    {
        Gate::authorize('verifyCreator', $creator);
        $proof = $badges->proof($creator);
        $badge = DB::table('creator_verifications')->where('user_id', $creator->id)->first();

        return response()->view('creators.verify', compact('creator', 'proof', 'badge'))->header('Cache-Control', 'no-store, private');
    }

    public function update(DecideCreatorBadgeRequest $request, User $creator, CreatorBadges $badges): RedirectResponse
    {
        $badges->decide($request->user(), $request->session()->getId(), $creator, $request->validated());

        return redirect()->route('creator-badges.show', $creator)->with('status', 'Badge decision saved.');
    }
}
