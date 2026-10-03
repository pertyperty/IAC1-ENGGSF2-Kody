<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ApplyContributorRequest;
use App\Models\ContributorApplication;
use App\Services\Account\ContributorApplications;
use App\Services\Account\ContributorEligibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ContributorApplicationController extends Controller
{
    public function create(Request $request, ContributorEligibility $eligibility): Response
    {
        Gate::authorize('viewOwn', ContributorApplication::class);
        $user = $request->user();
        $progress = $eligibility->snapshot($user);
        $history = ContributorApplication::where('user_id', $user->id)->latest('id')->paginate(10);
        $latest = ContributorApplication::where('user_id', $user->id)->latest('id')->first();
        $pendingInstructor = DB::table('instructor_applications')->where('user_id', $user->id)->where('verification_status', 'Pending')->exists();
        $canApply = $progress['eligible'] && ! $pendingInstructor && ($latest === null || $latest->approval_status === 'Rejected');

        return response()->view('account.contributor-application', compact('progress', 'history', 'latest', 'pendingInstructor', 'canApply'))->header('Cache-Control', 'no-store, private');
    }

    public function store(ApplyContributorRequest $request, ContributorApplications $applications): RedirectResponse
    {
        $applications->submit($request->user(), $request->session()->getId(), $request->validated(), $request->file('credential_document'));

        return redirect()->route('contributor-application.create')->with('status', 'Your Contributor application is awaiting review. Your Learner access remains available.');
    }
}
