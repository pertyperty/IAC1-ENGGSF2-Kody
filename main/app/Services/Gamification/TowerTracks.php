<?php

namespace App\Services\Gamification;

use App\Models\TowerLevel;

class TowerTracks
{
    public function all(): array
    {
        return [
            'beginner' => ['label' => 'Beginner', 'title' => 'Learn the foundations', 'start' => 1, 'end' => 25, 'description' => 'Sequences, loops, conditions, variables and first language bridges.'],
            'intermediate' => ['label' => 'Intermediate practice', 'title' => 'Apply the foundations', 'start' => 26, 'end' => 1000, 'description' => 'Combine state, Boolean rules, strings, stacks, boundaries and code reading. This track assumes foundation familiarity; entry is your choice, not a qualification.'],
        ];
    }

    public function key(TowerLevel $level): string
    {
        return $level->position <= 25 ? 'beginner' : 'intermediate';
    }

    public function for(TowerLevel $level): array
    {
        return $this->all()[$this->key($level)];
    }
}
