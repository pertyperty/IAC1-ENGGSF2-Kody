<?php

namespace App\Services\Gamification;

use App\Models\TowerLevel;
use Illuminate\Database\Eloquent\Builder;

class TowerTracks
{
    public function all(): array
    {
        return [
            'beginner' => ['label' => 'Beginner', 'title' => 'Build your coding habits', 'description' => 'Sequences, variables, loops and first programs. New practice connects predictions to edge cases.'],
            'intermediate' => ['label' => 'Intermediate practice', 'title' => 'Choose and test a strategy', 'description' => 'Combine state and data structures, then reason about searching, testing and efficiency.'],
            'expert' => ['label' => 'Expert', 'title' => 'Reason about algorithms', 'description' => 'Invariants, graph algorithms, dynamic programming and tradeoffs. Assumes familiarity with loops, collections and asymptotic notation; no certification is implied.'],
        ];
    }

    public function key(TowerLevel $level): string
    {
        return $level->skill_track ?? ($level->position <= 25 ? 'beginner' : 'intermediate');
    }

    public function for(TowerLevel $level): array
    {
        return $this->all()[$this->key($level)];
    }

    public function query(string $key): Builder
    {
        if (! array_key_exists($key, $this->all())) {
            throw new \InvalidArgumentException('Unknown Tower skill track.');
        }

        return TowerLevel::where(function (Builder $query) use ($key): void {
            $query->where('skill_track', $key);
            if ($key !== 'expert') {
                $query->orWhere(fn (Builder $legacy) => $legacy->whereNull('skill_track')->where('position', $key === 'beginner' ? '<=' : '>', 25));
            }
        });
    }
}
