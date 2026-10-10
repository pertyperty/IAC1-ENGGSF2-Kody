<?php

namespace App\Services\Engagement;

use App\Models\CodingChallenge;
use App\Models\ContributorApplication;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class NotificationDestinations
{
    public function for(User $user, Collection $notifications): array
    {
        $links = [];
        $gate = Gate::forUser($user);
        foreach ([
            ['module.reviewed', LearningModule::class, 'module_id', 'studio.edit'],
            ['course.reviewed', LearningCourse::class, 'course_id', 'courses.edit'],
            ['challenge.reviewed', CodingChallenge::class, 'challenge_id', 'challenges.edit'],
        ] as [$type, $model, $key, $route]) {
            $notices = $notifications->where('type', $type);
            if ($notices->isEmpty() || ! $gate->allows('create', $model)) {
                continue;
            }
            $targets = $model::whereIn('id', $notices->pluck('data.'.$key))->where('created_by', $user->id)
                ->get(['id', 'created_by', 'status'])->keyBy('id');
            foreach ($notices as $notice) {
                $target = $targets->get($notice->data[$key] ?? null);
                if ($target !== null && $gate->allows('viewOwned', $target)) {
                    $links[$notice->id] = route($route, $target);
                }
            }
        }
        $applications = $notifications->where('type', 'contributor.application');
        $targets = $applications->isEmpty() ? collect() : ContributorApplication::whereIn('id', $applications->pluck('data.application_id'))
            ->get(['id', 'user_id'])->keyBy('id');
        foreach ($applications as $notice) {
            $target = $targets->get($notice->data['application_id'] ?? null);
            if ($target === null) {
                continue;
            }
            if (($notice->data['reviewer'] ?? false) === true && $gate->allows('view', $target)) {
                $links[$notice->id] = route('contributor-reviews.show', $target);
            } elseif (($notice->data['reviewer'] ?? false) !== true && $target->user_id === $user->id && $gate->allows('viewOwn', ContributorApplication::class)) {
                $links[$notice->id] = route('contributor-application.create');
            }
        }
        foreach ($notifications->where('type', 'financial.notice') as $notice) {
            $links[$notice->id] = route('wallet.index');
        }

        return $links;
    }
}
