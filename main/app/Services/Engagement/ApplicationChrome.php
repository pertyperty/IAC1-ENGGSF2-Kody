<?php

namespace App\Services\Engagement;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\LearningCourse;
use App\Services\Account\RoleChangeNotices;
use App\Services\Gamification\Achievements;
use App\Services\Gamification\LearningProgression;
use App\Services\Gamification\TowerProgression;
use App\Services\Transactions\WalletLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationChrome
{
    public function snapshot(Request $request, array $viewData = []): array
    {
        $user = $request->user();
        $focused = $request->routeIs('account.edit', 'account.google', 'account.archive', 'account.delete',
            'studio.create', 'studio.edit', 'courses.create', 'courses.edit', 'challenges.create', 'challenges.edit',
            'game-presets.edit', 'module-media.show', 'site-management.*');
        // Only the dashboard controller supplies these owned, already-computed snapshots.
        $dashboard = $request->routeIs('dashboard') ? ($viewData['dashboard'] ?? null) : null;
        $progress = null;
        if (($user?->account_role === Role::Learner || ($user?->account_role === Role::Instructor && $request->routeIs('home', 'tower.*')))
            && $user->can('viewLearning', LearningCourse::class) && ! $focused) {
            $progress = $dashboard !== null && isset($viewData['progress'])
                ? $viewData['progress'] : app(LearningProgression::class)->snapshot($user->id);
        }
        $achievements = $progress !== null ? ($dashboard['achievements'] ?? app(Achievements::class)->snapshot($user->id)) : null;
        $tower = $progress === null ? null : app(TowerProgression::class)->counts($user->id);

        $wallet = $user !== null && $user->account_status === AccountStatus::Active && $user->email_verified_at !== null && ! $focused
            ? ($request->routeIs('wallet.index') && isset($viewData['wallet']) ? $viewData['wallet'] : app(WalletLedger::class)->snapshot($user->id)) : null;

        $announcement = ! $focused && $request->route() !== null && ! isset($viewData['exception'])
            && ! $request->routeIs('home', 'tower.*', 'login*', 'register*', 'recovery.*', 'verification.*')
            ? DB::table('site_announcements')->where('id', 1)->where('active', true)->first(['title', 'message', 'destination']) : null;

        $roleChange = ! $focused && ! isset($viewData['exception']) && $user?->account_status === AccountStatus::Active
            && $user->email_verified_at !== null && in_array($user->account_role, [Role::Learner, Role::Contributor, Role::Instructor], true)
            ? app(RoleChangeNotices::class)->snapshot($user, $request->session()->getId()) : null;

        return compact('focused', 'progress', 'achievements', 'tower', 'wallet', 'announcement', 'roleChange') + ['unread' => $dashboard['unread'] ?? $user?->unreadNotifications()->count() ?? 0];
    }
}
