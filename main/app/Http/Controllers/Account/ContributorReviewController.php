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
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContributorReviewController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', ContributorApplication::class);
        $applications = ContributorApplication::with('user')->orderByRaw("CASE WHEN approval_status = 'Pending' THEN 0 ELSE 1 END")->latest('id')->paginate(15);

        return response()->view('account.contributor-reviews', compact('applications'))->header('Cache-Control', 'no-store, private');
    }

    public function show(ContributorApplication $application): Response
    {
        Gate::authorize('view', $application);
        $application->load('user');

        return response()->view('account.contributor-review', compact('application'))->header('Cache-Control', 'no-store, private');
    }

    public function credential(Request $request, ContributorApplication $application, CredentialDocuments $documents): StreamedResponse
    {
        return $documents->download($request->user(), $application);
    }

    public function review(ReviewContributorRequest $request, ContributorApplication $application, ContributorApplications $applications): RedirectResponse
    {
        $applications->review($request->user(), $request->session()->getId(), $application, $request->validated());

        return redirect()->route('contributor-reviews.show', $application)->with('status', 'Application reviewed. The applicant will be notified.');
    }
}
