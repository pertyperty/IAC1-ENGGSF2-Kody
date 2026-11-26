<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\ReviewInstructorApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ReviewInstructorRequest;
use App\Models\InstructorApplication;
use App\Services\Account\CredentialDocuments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstructorReviewController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', InstructorApplication::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80', 'not_regex:/\x00/'], 'status' => ['nullable', 'in:Pending,Approved,Rejected']]);
        $applications = InstructorApplication::with('user')->when($filters['status'] ?? null, fn ($query, $status) => $query->where('verification_status', $status))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->whereHas('user', fn ($user) => $user->where('username', 'ilike', '%'.addcslashes($search, '%_\\').'%')))
            ->orderByRaw("CASE WHEN verification_status = 'Pending' THEN 0 ELSE 1 END")->orderBy('id')->paginate(15)->withQueryString();

        return response()->view('account.instructor-reviews', compact('applications', 'filters'))->header('Cache-Control', 'no-store, private');
    }

    public function show(InstructorApplication $application, CredentialDocuments $documents): Response
    {
        Gate::authorize('view', $application);
        $application->load('user.creatorVerification');

        $versions = DB::table('instructor_application_versions')->where('instructor_application_id', $application->id)->orderByDesc('application_version')->paginate(10);
        $documentMeta = $documents->metadata($application);
        $versionMeta = [];
        foreach ($versions as $version) {
            $versionMeta[$version->application_version] = $documents->metadata($application, (int) $version->application_version);
        }
        $badgeRequest = DB::table('creator_badge_requests')
            ->where('user_id', $application->user_id)
            ->where('role', $application->user->account_role->value)
            ->whereNull('resolved_at')
            ->first();

        return response()->view('account.instructor-review', compact('application', 'versions', 'documentMeta', 'versionMeta', 'badgeRequest'))->header('Cache-Control', 'no-store, private');
    }

    public function credential(Request $request, InstructorApplication $application, CredentialDocuments $documents): StreamedResponse
    {
        return $request->boolean('inline')
            ? $documents->inline($request->user(), $application)
            : $documents->download($request->user(), $application);
    }

    public function review(ReviewInstructorRequest $request, InstructorApplication $application, ReviewInstructorApplication $review): JsonResponse|RedirectResponse
    {
        $review->handle($request->user(), $request->session()->getId(), $application, (int) $request->validated('record_version'),
            $request->validated('decision'), $request->validated('verification_notes'), $request->boolean('credibility_reviewed'));

        return $request->expectsJson() ? response()->json(['message' => 'Application reviewed. The applicant will be notified.'])->header('Cache-Control', 'no-store')
            : redirect()->route('instructor-reviews.show', $application)->with('status', 'Application reviewed. The applicant will be notified.');
    }

    public function versionCredential(Request $request, InstructorApplication $application, int $version, CredentialDocuments $documents): StreamedResponse
    {
        return $request->boolean('inline')
            ? $documents->inline($request->user(), $application, $version)
            : $documents->download($request->user(), $application, $version);
    }
}
