<?php

namespace App\Http\Controllers\Gamification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gamification\ConfigureWeeklyEventRequest;
use App\Models\CodingChallengeRevision;
use App\Models\WeeklyEvent;
use App\Services\Gamification\WeeklyEvents;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class WeeklyEventStudioController extends Controller
{
    public function index(Request $request, WeeklyEvents $events): Response
    {
        Gate::authorize('manage', WeeklyEvent::class);
        $data = $request->validate(['week' => ['nullable', 'date_format:Y-m-d'], 'q' => ['nullable', 'string', 'max:80']]);
        $week = $data['week'] ?? $events->weekStart()->toDateString();
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $week, 'Asia/Manila');
        $event = WeeklyEvent::where('starts_at', $start->utc())->with('revision:id,title,language')->first();
        $query = trim($data['q'] ?? '');
        $revisions = CodingChallengeRevision::where('review_status', 'Approved')
            ->whereHas('challenge', fn ($builder) => $builder->where('status', 'Published')->whereColumn('published_revision_id', 'coding_challenge_revisions.id'))
            ->when($query !== '', fn ($builder) => $builder->where('title', 'ilike', '%'.addcslashes($query, '%_\\').'%'))
            ->orderByDesc('id')->paginate(12, ['id', 'challenge_id', 'title', 'language'])->withQueryString();
        $upcoming = WeeklyEvent::with('revision:id,title,language')->orderByDesc('starts_at')->limit(12)->get();
        $editable = $event === null || ($event->status === 'Scheduled' && $event->starts_at->isFuture());

        return response()->view('weekly.studio', compact('week', 'event', 'revisions', 'upcoming', 'editable', 'query'))->header('Cache-Control', 'no-store, private');
    }

    public function store(ConfigureWeeklyEventRequest $request, WeeklyEvents $events): RedirectResponse
    {
        $event = $events->configure($request->user(), $request->session()->getId(), $request->validated());

        return redirect()->route('weekly-studio.index', ['week' => $event->starts_at->setTimezone('Asia/Manila')->toDateString()])->with('status', 'Weekly quest configured.');
    }
}
