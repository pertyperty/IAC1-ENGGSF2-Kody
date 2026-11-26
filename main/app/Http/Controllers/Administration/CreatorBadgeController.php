<?php

namespace App\Http\Controllers\Administration;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\DecideCreatorBadgeRequest;
use App\Models\User;
use App\Services\Account\CredentialDocuments;
use App\Services\Administration\CreatorBadges;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreatorBadgeController extends Controller
{
    public function show(User $creator, CreatorBadges $badges, CredentialDocuments $documents): Response
    {
        Gate::authorize('verifyCreator', $creator);
        $creator->loadMissing('creatorVerification');
        $proof = $badges->proof($creator);
        $documentMeta = $proof ? $documents->metadata($proof) : null;
        $credentialRoute = $creator->account_role === Role::Instructor ? 'instructor-reviews.credential' : 'contributor-reviews.credential';
        $proofDownloadUrl = $proof ? route($credentialRoute, $proof) : null;
        $proofInlineUrl = $proof ? route($credentialRoute, [$proof, 'inline' => 1]) : null;
        $badge = DB::table('creator_verifications')->where('user_id', $creator->id)->first();

        $badgeRequest = DB::table('creator_badge_requests')->where('user_id', $creator->id)->where('role', $creator->account_role->value)->whereNull('resolved_at')->first();

        return response()->view('creators.verify', compact('creator', 'proof', 'documentMeta', 'proofDownloadUrl', 'proofInlineUrl', 'badge', 'badgeRequest'))->header('Cache-Control', 'no-store, private');
    }

    public function update(DecideCreatorBadgeRequest $request, User $creator, CreatorBadges $badges): RedirectResponse
    {
        $badges->decide($request->user(), $request->session()->getId(), $creator, $request->validated());

        return redirect()->route('creator-badges.show', $creator)->with('status', 'Badge decision saved.');
    }
}
