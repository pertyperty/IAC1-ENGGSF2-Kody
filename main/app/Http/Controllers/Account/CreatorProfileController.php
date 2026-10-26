<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\SaveCreatorProfileRequest;
use App\Models\CodingChallenge;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Account\CreatorProfiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CreatorProfileController extends Controller
{
    public function show(User $creator, CreatorProfiles $profiles): Response
    {
        abort_unless($profiles->visible($creator), 404);
        $profile = DB::table('creator_profiles')->where('user_id', $creator->id)->first();
        $content = [];
        foreach (['Lessons' => LearningModule::class, 'Courses' => LearningCourse::class, 'Coding challenges' => CodingChallenge::class] as $label => $model) {
            $query = $model::where('created_by', $creator->id)->where('status', 'Published')->whereNull('staff_withdrawn_at')
                ->whereHas('publishedRevision', fn ($revision) => $revision->where('review_status', 'Approved'));
            $content[$label] = ['total' => (clone $query)->count(), 'items' => $query->with('publishedRevision')->orderByDesc('id')->limit(6)->get()];
        }

        return response()->view('creators.show', compact('creator', 'profile', 'content'))->header('Cache-Control', 'no-store, private');
    }

    public function edit(Request $request, CreatorProfiles $profiles): Response
    {
        abort_unless($profiles->visible($request->user()), 403);
        $profile = DB::table('creator_profiles')->where('user_id', $request->user()->id)->first();

        return response()->view('creators.edit', compact('profile'))->header('Cache-Control', 'no-store, private');
    }

    public function update(SaveCreatorProfileRequest $request, CreatorProfiles $profiles): RedirectResponse
    {
        $profiles->save($request->user(), $request->session()->getId(), $request->validated());

        return redirect()->route('creators.show', $request->user())->with('status', 'Public profile saved.');
    }
}
