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
    public function index(): Response
    {
        Gate::authorize('viewAny', InstructorApplication::class);
        $applications = InstructorApplication::with('user')->orderByRaw("CASE WHEN verification_status = 'Pending' THEN 0 ELSE 1 END")->orderBy('id')->paginate(15);

        return response()->view('account.instructor-reviews', compact('applications'))->header('Cache-Control', 'no-store, private');
    }

    public function show(InstructorApplication $application): Response
    {
        Gate::authorize('view', $application);
        $application->load('user');

        $versions = DB::table('instructor_application_versions')->where('instructor_application_id', $application->id)->orderByDesc('application_version')->paginate(10);

        return response()->view('account.instructor-review', compact('application', 'versions'))->header('Cache-Control', 'no-store, private');
    }

    public function credential(Request $request, InstructorApplication $application, CredentialDocuments $documents): StreamedResponse
    {
        return $documents->download($request->user(), $application);
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
        return $documents->download($request->user(), $application, $version);
    }
}
