<?php

namespace App\Services\Engagement;

class LearnerMissions
{
    /** Display goals derived from saved progression, without issuing new rewards. */
    public function snapshot(array $progress, array $achievements): array
    {
        $next = $progress['next_level'];
        $levels = count($progress['levels']);
        $rank = $achievements['next'];
        $highestRank = (int) array_key_last(config('economy.ranks'));

        return [
            ['title' => 'Save a daily win', 'icon' => '✦', 'done' => $progress['active_today'],
                'description' => $progress['active_today'] ? 'Today is saved. Replay for practice whenever you like.' : 'Clear a game or quiz to keep your Manila daily streak growing.',
                'value' => (int) $progress['active_today'], 'max' => 1, 'label' => $progress['active_today'] ? 'Practice again' : 'Play today',
                'url' => route('learning.show', $next ?? array_key_first($progress['levels']))],
            ['title' => 'Clear the starter trail', 'icon' => '↗', 'done' => $next === null,
                'description' => $next === null ? 'You cleared your starter trail! Discover a creator adventure.'
                    : ($progress['completed_count'] === 0 ? 'Start your first saved adventure. ' : 'Next up: '.$progress['levels'][$next]['title'].'. ')
                    .$progress['completed_count'].' of '.$levels.' starter levels cleared.',
                'value' => $progress['completed_count'], 'max' => max(1, $levels), 'label' => $next === null ? 'Explore creator adventures' : 'Continue playing',
                'url' => $next === null ? route('learning.catalog') : route('learning.show', $next)],
            ['title' => $rank ? 'Grow toward '.$rank['name'] : 'You reached Architect', 'icon' => '◇', 'done' => $rank === null,
                'description' => $rank ? number_format(max(0, $rank['threshold'] - $achievements['xp'])).' XP to your next rank. First-time verified wins earn XP.' : 'The top XP rank is yours. There are still new ideas to explore.',
                'value' => min($achievements['xp'], $rank['threshold'] ?? $highestRank), 'max' => $rank['threshold'] ?? $highestRank,
                'label' => 'View achievement ranks', 'url' => route('leaderboards.index')],
        ];
    }
}
