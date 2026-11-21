<?php

namespace App\Http\Controllers\Account;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\SaveCreatorProfileRequest;
use App\Models\CodingChallenge;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Account\CreatorProfiles;
use App\Services\Account\CredentialDocuments;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use App\Services\Administration\CreatorBadges;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatorProfileController extends Controller
{
    public function show(User $creator, CreatorProfiles $profiles): Response
    {
        abort_unless($profiles->visible($creator), 404);
        $profile = DB::table('creator_profiles')->where('user_id', $creator->id)->first();
        $content = [];
        foreach (['Modules' => LearningModule::class, 'Courses' => LearningCourse::class, 'Coding challenges' => CodingChallenge::class] as $label => $model) {
            $query = $model::where('created_by', $creator->id)->where('status', 'Published')->whereNull('staff_withdrawn_at')
                ->whereHas('publishedRevision', fn ($revision) => $revision->where('review_status', 'Approved'));
            $content[$label] = ['total' => (clone $query)->count(), 'items' => $query->select(['id', 'published_revision_id'])
                ->with('publishedRevision:id,title')->orderByDesc('id')->limit(6)->get()];
        }

        return response()->view('creators.show', compact('creator', 'profile', 'content'))->header('Cache-Control', 'no-store, private');
    }

    public function edit(Request $request, CreatorProfiles $profiles): Response
    {
        abort_unless($profiles->visible($request->user()), 403);
        $profile = DB::table('creator_profiles')->where('user_id', $request->user()->id)->first();

        $badgeRequest = DB::table('creator_badge_requests')->where('user_id', $request->user()->id)->where('role', $request->user()->account_role->value)->whereNull('resolved_at')->first();

        return response()->view('creators.edit', compact('profile', 'badgeRequest'))->header('Cache-Control', 'no-store, private');
    }

    public function requestBadge(Request $request): RedirectResponse
    {
        $request->validate(['confirmed' => ['required', 'accepted']]);
        DB::transaction(function () use ($request): void {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $request->session()->getId());
            abort_unless(in_array($user->account_role, [Role::Instructor, Role::Contributor], true), 403);
            $proof = app(CreatorBadges::class)->proof($user);
            if ($user->hasCreatorBadge() || $proof === null || ! app(CredentialDocuments::class)->available($proof)) {
                throw ValidationException::withMessages(['confirmed' => 'An approved role application with available private supporting documents is required.']);
            }
            $pending = DB::table('creator_badge_requests')->where('user_id', $user->id)->first();
            if ($pending && $pending->role === $user->account_role->value && $pending->resolved_at === null) {
                return;
            }
            DB::table('creator_badge_requests')->updateOrInsert(['user_id' => $user->id], ['role' => $user->account_role->value, 'requested_at' => now(), 'resolved_at' => null]);
            app(AuditRecorder::class)->record($user->id, $user->id, 'creator_badge.requested', 'user', (string) $user->id, ['role' => $user->account_role->value]);
        });

        return redirect()->route('creator-profile.edit')->with('status', 'Qualifications review requested. Staff will check your existing private documents.');
    }

    public function update(SaveCreatorProfileRequest $request, CreatorProfiles $profiles): RedirectResponse
    {
        $profiles->save($request->user(), $request->session()->getId(), $request->validated());

        return redirect()->route('creators.show', $request->user())->with('status', 'Public profile saved.');
    }
}
