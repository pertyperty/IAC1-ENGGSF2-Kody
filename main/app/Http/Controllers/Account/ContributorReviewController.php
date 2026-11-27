<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ReviewContributorRequest;
use App\Models\ContributorApplication;
use App\Services\Account\ContributorApplications;
use App\Services\Account\CredentialDocuments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContributorReviewController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ContributorApplication::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80', 'not_regex:/\x00/'], 'status' => ['nullable', 'in:Pending,Approved,Rejected']]);
        $applications = ContributorApplication::with('user')->when($filters['status'] ?? null, fn ($query, $status) => $query->where('approval_status', $status))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->whereHas('user', fn ($user) => $user->where('username', 'ilike', '%'.addcslashes($search, '%_\\').'%')))
            ->orderByRaw("CASE WHEN approval_status = 'Pending' THEN 0 ELSE 1 END")->latest('id')->paginate(15)->withQueryString();

        return response()->view('account.contributor-reviews', compact('applications', 'filters'))->header('Cache-Control', 'no-store, private');
    }

    public function show(ContributorApplication $application, CredentialDocuments $documents): Response
    {
        Gate::authorize('view', $application);
        $application->load('user.creatorVerification');
        $documentMeta = $documents->metadata($application);
        $badgeRequest = DB::table('creator_badge_requests')
            ->where('user_id', $application->user_id)
            ->where('role', $application->user->account_role->value)
            ->whereNull('resolved_at')
            ->first();

        return response()->view('account.contributor-review', compact('application', 'documentMeta', 'badgeRequest'))->header('Cache-Control', 'no-store, private');
    }

    public function credential(Request $request, ContributorApplication $application, CredentialDocuments $documents): StreamedResponse
    {
        return $request->boolean('inline')
            ? $documents->inline($request->user(), $application)
            : $documents->download($request->user(), $application);
    }

    public function review(ReviewContributorRequest $request, ContributorApplication $application, ContributorApplications $applications): RedirectResponse
    {
        $applications->review($request->user(), $request->session()->getId(), $application, $request->validated());

        return redirect()->route('contributor-reviews.show', $application)->with('status', 'Application reviewed. The applicant will be notified.');
    }
}
