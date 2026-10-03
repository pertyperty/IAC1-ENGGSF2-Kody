<?php

namespace App\Http\Controllers\Account;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ApplyInstructorRequest;
use App\Models\InstructorApplication;
use App\Services\Account\InstructorApplications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class InstructorApplicationController extends Controller
{
    public function create(Request $request): Response
    {
        abort_unless(in_array($request->user()->account_role, [Role::Learner, Role::Contributor], true), 403);
        $application = InstructorApplication::where('user_id', $request->user()->id)->first(['id', 'verification_status', 'verification_notes', 'record_version']);
        $history = $application === null ? collect() : DB::table('instructor_application_versions')
            ->where('instructor_application_id', $application->id)->orderByDesc('application_version')
            ->paginate(10, ['application_version', 'verification_status', 'verification_notes', 'created_at']);

        return response()->view('account.instructor-application', compact('application', 'history'))->header('Cache-Control', 'no-store, private');
    }

    public function store(ApplyInstructorRequest $request, InstructorApplications $applications): RedirectResponse
    {
        $applications->submit($request->user(), $request->session()->getId(), $request->validated(), $request->file('credential_document'));

        return redirect()->route('instructor-application.create')->with('status', 'Your creator application is awaiting review. Your current account access remains available.');
    }
}
