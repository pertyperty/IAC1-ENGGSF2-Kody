<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ReviewContributorRequest;
use App\Models\ContributorApplication;
use App\Services\Account\ContributorApplications;
use App\Services\Administration\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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

    public function credential(Request $request, ContributorApplication $application): StreamedResponse
    {
        Gate::authorize('view', $application);
        $disk = $application->credential_disk;
        $path = $application->credential_path;
        abort_if($disk === 'public' || config('filesystems.disks.'.$disk.'.visibility') === 'public'
            || ! str_starts_with($path, 'contributor-credentials/') || str_contains($path, '..') || str_contains($path, '\\'), 404);
        abort_unless(Storage::disk($disk)->exists($path), 404);
        app(AuditRecorder::class)->record($request->user()->id, $application->user_id, 'contributor_application.credential_downloaded', 'contributor_application', (string) $application->id);

        return Storage::disk($disk)->download($path, 'contributor-credential.'.pathinfo($path, PATHINFO_EXTENSION),
            ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private']);
    }

    public function review(ReviewContributorRequest $request, ContributorApplication $application, ContributorApplications $applications): RedirectResponse
    {
        $applications->review($request->user(), $request->session()->getId(), $application, $request->validated());

        return redirect()->route('contributor-reviews.show', $application)->with('status', 'Application reviewed. The applicant will be notified.');
    }
}
