<?php

namespace App\Http\Controllers\Account;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\CodingChallengeRevision;
use App\Models\CourseRevision;
use App\Models\ModuleRevision;
use App\Services\Account\CreatorErasure;
use App\Services\Administration\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreatorErasureController extends Controller
{
    public function request(Request $request, CreatorErasure $erasure): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted'], 'retention_consent' => ['required', 'accepted']]);
        $erasure->request($request->user(), $request->session()->getId(), $data);

        return redirect()->route('account.delete')->with('status', 'Your retained content is awaiting a manual privacy review.');
    }

    public function index(Request $request): Response
    {
        $this->staff($request);
        $reviews = DB::table('creator_erasure_reviews')->where('state', 'Pending')->orderBy('created_at')->paginate(15);

        return response()->view('account.creator-erasure-reviews', compact('reviews'))->header('Cache-Control', 'no-store, private');
    }

    public function show(Request $request, string $review): Response
    {
        $this->staff($request);
        $row = DB::table('creator_erasure_reviews')->find($review) ?? abort(404);
        $kind = $request->validate(['kind' => ['sometimes', Rule::in(['module', 'course', 'challenge'])]])['kind'] ?? 'module';
        [$model, $relationship] = match ($kind) {
            'module' => [ModuleRevision::class, 'module'], 'course' => [CourseRevision::class, 'course'],
            'challenge' => [CodingChallengeRevision::class, 'challenge'],
        };
        $query = $model::whereHas($relationship, fn ($q) => $q->where('created_by', $row->user_id));
        if ($kind === 'challenge') {
            $query->with('testCases');
        }
        if ($kind === 'course') {
            $query->with('modules');
        }
        $revisions = $query->orderBy('id')->paginate(5)->withQueryString();
        app(AuditRecorder::class)->record($request->user()->id, $row->user_id, 'creator.privacy_inventory_viewed', 'creator_erasure_review', $review, ['kind' => $kind, 'page' => $revisions->currentPage()]);

        return response()->view('account.creator-erasure-review', compact('row', 'kind', 'revisions'))->header('Cache-Control', 'no-store, private');
    }

    public function review(Request $request, string $review, CreatorErasure $erasure): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['Approved', 'Rejected'])], 'review_notes' => ['required', 'string', 'max:500'],
            'privacy_reviewed' => ['sometimes', 'accepted'], 'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']]);
        $erasure->review($request->user(), $request->session()->getId(), $review, $data);

        return redirect()->route('creator-erasure.index')->with('status', 'Manual privacy review recorded.');
    }

    private function staff(Request $request): void
    {
        abort_unless(in_array($request->user()->account_role, [Role::Moderator, Role::Administrator], true), 403);
    }
}
