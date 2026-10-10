<?php

namespace App\Services\Engagement;

use App\Enums\Role;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Services\Gamification\Achievements;
use App\Services\Gamification\LearningProgression;
use App\Services\Gamification\TowerProgression;
use Illuminate\Http\Request;

class ApplicationChrome
{
    public function snapshot(Request $request, array $viewData = []): array
    {
        $user = $request->user();
        $focused = $request->routeIs('account.edit', 'account.google', 'account.archive', 'account.delete',
            'studio.create', 'studio.edit', 'courses.create', 'courses.edit', 'challenges.create', 'challenges.edit',
            'game-presets.create', 'game-presets.edit');
        // Only the dashboard controller supplies these owned, already-computed snapshots.
        $dashboard = $request->routeIs('dashboard') ? ($viewData['dashboard'] ?? null) : null;
        $progress = null;
        if ($user?->account_role === Role::Learner && $user->can('viewLearning', LearningCourse::class) && ! $focused) {
            $progress = $dashboard !== null && isset($viewData['progress'])
                ? $viewData['progress'] : app(LearningProgression::class)->snapshot($user->id);
        }
        $achievements = $progress !== null ? ($dashboard['achievements'] ?? app(Achievements::class)->snapshot($user->id)) : null;
        $back = ['url' => route($user ? 'dashboard' : 'home'), 'label' => $user ? 'Back to your workspace' : 'Back to home'];
        foreach (['studio.*' => ['studio.index', 'Back to module studio'], 'courses.*' => ['courses.index', 'Back to course builder'],
            'curriculum.*' => ['courses.index', 'Back to course builder'], 'module-reviews.*' => ['module-reviews.index', 'Back to module reviews'],
            'course-reviews.*' => ['course-reviews.index', 'Back to course reviews'], 'challenge-reviews.*' => ['challenge-reviews.index', 'Back to challenge reviews'],
            'account-governance.*' => ['account-governance.index', 'Back to community accounts'],
            'instructor-reviews.*' => ['instructor-reviews.index', 'Back to Instructor applications'],
            'contributor-reviews.*' => ['contributor-reviews.index', 'Back to Contributor applications'],
            'game-presets.*' => ['game-presets.index', 'Back to game presets'], 'faq-management.*' => ['faq-management.index', 'Back to FAQ management'],
            'tower.*' => ['home', 'Back to the tower'], 'tower-studio.*' => ['tower-studio.index', 'Back to tower studio'],
            'creator-erasure.*' => ['creator-erasure.index', 'Back to privacy reviews'], 'finance.*' => ['finance.index', 'Back to accounting'],
            'wallet.*' => ['wallet.index', 'Back to my wallet'], 'earnings.*' => ['wallet.index', 'Back to my wallet'],
            'help.*' => ['help.index', 'Back to Help'],
            'modules.*' => ['learning.catalog', 'Back to learning catalog'],
            'challenge-attempts.*' => ['challenges.catalog', 'Back to coding quests'], 'weekly-events.*' => ['weekly-events.index', 'Back to weekly quests']] as $pattern => [$route, $label]) {
            if ($request->routeIs($pattern) && ! $request->routeIs($route)) {
                $back = ['url' => route($route), 'label' => $label];
                break;
            }
        }
        if ($request->routeIs('challenges.create', 'challenges.edit', 'challenges.archive-confirmation')) {
            $back = ['url' => route('challenges.index'), 'label' => 'Back to quest studio'];
        } elseif ($request->routeIs('challenges.show')) {
            $back = ['url' => route('challenges.catalog'), 'label' => 'Back to coding quests'];
        } elseif ($request->routeIs('account.*') && ! $request->routeIs('account.show')) {
            $back = ['url' => route('account.show'), 'label' => 'Back to my account'];
        } elseif ($request->routeIs('course-learning.lesson')) {
            $back = ['url' => route('course-learning.show', $request->route('course')), 'label' => 'Back to this course'];
        } elseif ($request->routeIs('course-learning.show')) {
            $back = ['url' => route('course-learning.catalog'), 'label' => 'Back to courses'];
        } elseif ($request->routeIs('module-media.show')) {
            if ($request->integer('course') > 0 && $request->integer('slot') > 0) {
                $back = ['url' => route('course-learning.lesson', [$request->integer('course'), $request->integer('slot')]), 'label' => 'Back to this lesson'];
            } elseif ($user?->can('viewOwned', $request->route('module'))) {
                $back = ['url' => route('studio.edit', $request->route('module')), 'label' => 'Back to module studio'];
            } elseif ($user?->can('viewAny', LearningModule::class)) {
                $back = ['url' => route('module-reviews.show', $request->route('module')), 'label' => 'Back to module review'];
            } else {
                $back = ['url' => route('modules.show', $request->route('module')), 'label' => 'Back to this lesson'];
            }
        }

        $tower = $progress === null ? null : app(TowerProgression::class)->counts($user->id);

        return compact('focused', 'progress', 'achievements', 'back', 'tower') + ['unread' => $dashboard['unread'] ?? $user?->unreadNotifications()->count() ?? 0];
    }
}
