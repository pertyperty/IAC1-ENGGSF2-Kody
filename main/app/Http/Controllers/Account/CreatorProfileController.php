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
    public function show(Request $request, User $creator, CreatorProfiles $profiles): Response
    {
        abort_unless($profiles->visible($creator), 404);
        $creator->loadMissing('creatorVerification');
        $filters = $request->validate([
            'tab' => ['nullable', 'in:all,modules,courses,challenges'],
            'q' => ['nullable', 'string', 'max:80', 'not_regex:/\x00/'],
        ]);
        $activeTab = $filters['tab'] ?? 'all';
        $search = trim($filters['q'] ?? '');
        $profile = DB::table('creator_profiles')->where('user_id', $creator->id)->first();
        $socials = $profiles->socials($profile);

        $groups = [
            'Modules' => ['model' => LearningModule::class, 'tab' => 'modules', 'page' => 'modules_page'],
            'Courses' => ['model' => LearningCourse::class, 'tab' => 'courses', 'page' => 'courses_page'],
            'Coding challenges' => ['model' => CodingChallenge::class, 'tab' => 'challenges', 'page' => 'challenges_page'],
        ];

        $content = [];
        $totals = [];
        foreach ($groups as $label => $meta) {
            $base = $meta['model']::where('created_by', $creator->id)
                ->where('status', 'Published')
                ->whereNull('staff_withdrawn_at')
                ->whereHas('publishedRevision', fn ($revision) => $revision->where('review_status', 'Approved'));
            $totals[$meta['tab']] = (clone $base)->count();

            $filtered = (clone $base)->when($search !== '', fn ($builder) => $builder->whereHas('publishedRevision', fn ($revision) => $revision
                ->where('review_status', 'Approved')
                ->where('title', 'ilike', '%'.addcslashes($search, '%_\\').'%')));

            $perPage = $activeTab === $meta['tab'] ? 12 : 6;
            $paginator = $filtered->select(['id', 'published_revision_id'])
                ->with('publishedRevision:id,title')
                ->orderByDesc('id')
                ->paginate($perPage, ['*'], $meta['page'])
                ->withQueryString();

            $content[$label] = [
                'total' => $paginator->total(),
                'items' => $paginator->getCollection(),
                'paginator' => $paginator,
                'tab' => $meta['tab'],
            ];
        }

        return response()->view('creators.show', compact('creator', 'profile', 'socials', 'content', 'totals', 'activeTab', 'search'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function edit(Request $request, CreatorProfiles $profiles): Response
    {
        abort_unless($profiles->visible($request->user()), 403);
        $profile = DB::table('creator_profiles')->where('user_id', $request->user()->id)->first();
        $socials = $profiles->socials($profile);

        $badgeRequest = DB::table('creator_badge_requests')->where('user_id', $request->user()->id)->where('role', $request->user()->account_role->value)->whereNull('resolved_at')->first();

        return response()->view('creators.edit', compact('profile', 'socials', 'badgeRequest'))->header('Cache-Control', 'no-store, private');
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
