<?php

namespace App\Services\Engagement;

use App\Models\LearningCourse;
use App\Services\Gamification\Achievements;
use App\Services\Gamification\LearningProgression;
use Illuminate\Http\Request;

class ApplicationChrome
{
    public function snapshot(Request $request): array
    {
        $user = $request->user();
        $focused = $request->routeIs('account.edit', 'account.google', 'account.archive', 'account.delete',
            'studio.create', 'studio.edit', 'courses.create', 'courses.edit', 'challenges.create', 'challenges.edit',
            'game-presets.create', 'game-presets.edit');
        $progress = $user?->can('viewLearning', LearningCourse::class) && ! $focused
            ? app(LearningProgression::class)->snapshot($user->id) : null;
        $achievements = $progress !== null ? app(Achievements::class)->snapshot($user->id) : null;
        $back = ['url' => route($user ? 'dashboard' : 'home'), 'label' => $user ? 'Back to your workspace' : 'Back to home'];
        foreach (['studio.*' => ['studio.index', 'Back to module studio'], 'courses.*' => ['courses.index', 'Back to course builder'],
            'curriculum.*' => ['courses.index', 'Back to course builder'], 'module-reviews.*' => ['module-reviews.index', 'Back to module reviews'],
            'course-reviews.*' => ['course-reviews.index', 'Back to course reviews'], 'challenge-reviews.*' => ['challenge-reviews.index', 'Back to challenge reviews'],
            'account-governance.*' => ['account-governance.index', 'Back to community accounts'],
            'instructor-reviews.*' => ['instructor-reviews.index', 'Back to Instructor applications'],
            'contributor-reviews.*' => ['contributor-reviews.index', 'Back to Contributor applications'],
            'game-presets.*' => ['game-presets.index', 'Back to game presets'], 'faq-management.*' => ['faq-management.index', 'Back to FAQ management'],
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
        }

        return compact('focused', 'progress', 'achievements', 'back') + ['unread' => $user?->unreadNotifications()->count() ?? 0];
    }
}
