<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\ReviewInstructorApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ReviewInstructorRequest;
use App\Models\InstructorApplication;
use App\Services\Administration\AuditRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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

        return response()->view('account.instructor-review', compact('application'))->header('Cache-Control', 'no-store, private');
    }

    public function credential(Request $request, InstructorApplication $application): StreamedResponse
    {
        Gate::authorize('view', $application);
        $disk = $application->credential_disk;
        $path = $application->credential_path;
        abort_if($disk === 'public' || config('filesystems.disks.'.$disk.'.visibility') === 'public'
            || ! str_starts_with($path, 'instructor-credentials/') || str_contains($path, '..'), 404);
        abort_unless(Storage::disk($disk)->exists($path), 404);
        app(AuditRecorder::class)->record($request->user()->id, $application->user_id, 'instructor_application.credential_downloaded', 'instructor_application', (string) $application->id);

        return Storage::disk($disk)->download($path, 'instructor-credential.'.pathinfo($path, PATHINFO_EXTENSION),
            ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private']);
    }

    public function review(ReviewInstructorRequest $request, InstructorApplication $application, ReviewInstructorApplication $review): JsonResponse|RedirectResponse
    {
        $review->handle($request->user(), $request->session()->getId(), $application, (int) $request->validated('record_version'),
            $request->validated('decision'), $request->validated('verification_notes'), $request->boolean('credibility_reviewed'));

        return $request->expectsJson() ? response()->json(['message' => 'Application reviewed. The applicant will be notified.'])->header('Cache-Control', 'no-store')
            : redirect()->route('instructor-reviews.show', $application)->with('status', 'Application reviewed. The applicant will be notified.');
    }
}
