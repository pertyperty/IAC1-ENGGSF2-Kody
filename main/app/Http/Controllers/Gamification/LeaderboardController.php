<?php

namespace App\Http\Controllers\Gamification;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\WeeklyEvent;
use App\Services\Gamification\WeeklyResults;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index(): Response
    {
        $ranked = DB::table('xp_totals as xp')->join('users as player', 'player.id', '=', 'xp.user_id')->where('xp.xp', '>', 0)
            ->where('player.account_status', AccountStatus::Active->value)->whereNotNull('player.email_verified_at')
            ->whereIn('player.account_role', [Role::Learner->value, Role::Contributor->value, Role::Instructor->value])
            ->selectRaw('xp.user_id, player.username, xp.xp, rank() OVER (ORDER BY xp.xp DESC) as position');
        $players = DB::query()->fromSub($ranked, 'ranked')->orderBy('position')->orderBy('user_id')->paginate(25);
        $events = DB::table('weekly_result_sets as results')->join('weekly_events as event', 'event.id', '=', 'results.event_id')
            ->where('results.status', 'Published')->orderByDesc('event.starts_at')->limit(12)->get(['event.id', 'event.starts_at']);

        return response()->view('weekly.leaderboards', compact('players', 'events'))->header('Cache-Control', 'no-store, private');
    }

    public function show(int $event): Response
    {
        $set = DB::table('weekly_result_sets')->where('event_id', $event)->where('status', 'Published')->firstOrFail();
        $week = WeeklyEvent::findOrFail($event);
        $results = $this->rows($event);

        return response()->view('weekly.results', ['week' => $week, 'results' => $results, 'set' => $set, 'preview' => false])->header('Cache-Control', 'no-store, private');
    }

    public function review(Request $request, WeeklyEvent $weeklyEvent, WeeklyResults $service): Response
    {
        abort_unless(in_array($request->user()->account_role, [Role::Moderator, Role::Administrator], true), 403);
        $service->prepare($weeklyEvent->id);
        $set = DB::table('weekly_result_sets')->where('event_id', $weeklyEvent->id)->firstOrFail();

        return response()->view('weekly.results', ['week' => $weeklyEvent->fresh(), 'results' => $this->rows($weeklyEvent->id), 'set' => $set, 'preview' => true])->header('Cache-Control', 'no-store, private');
    }

    public function publish(Request $request, WeeklyEvent $weeklyEvent, WeeklyResults $service): RedirectResponse
    {
        $data = $request->validate(['record_version' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']]);
        $service->publish($request->user(), $request->session()->getId(), $weeklyEvent->id, (int) $data['record_version'], $request->boolean('confirmed'));

        return redirect()->route('weekly-results.show', $weeklyEvent->id)->with('status', 'Verified weekly results published.');
    }

    private function rows(int $event): LengthAwarePaginator
    {
        return DB::table('weekly_results as result')->join('users as player', 'player.id', '=', 'result.user_id')->where('event_id', $event)
            ->orderBy('rank')->orderBy('result.user_id')->paginate(25, ['rank', 'score', 'reward_kb', 'player.username', 'player.account_status']);
    }
}
