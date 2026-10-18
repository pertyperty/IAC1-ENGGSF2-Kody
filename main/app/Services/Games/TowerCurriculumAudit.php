<?php

namespace App\Services\Games;

use RuntimeException;

class TowerCurriculumAudit
{
    private function circuitSolution(array $scenario): array
    {
        $desired = array_fill(0, count($scenario['inputs']), false);
        if ($scenario['target']) {
            $desired = $scenario['gate'] === 'and' ? array_fill(0, count($desired), true) : array_replace($desired, [0 => true]);
        }
        $program = [];
        foreach ($desired as $index => $value) {
            if ($value !== $scenario['inputs'][$index]) {
                $program[] = 'flip '.($index + 1);
            }
        }

        return $program ?: ['flip 1', 'flip 1'];
    }

    private function textSolution(array $scenario): array
    {
        $queue = [[$scenario['start'], []]];
        $visited = [];
        while ($queue !== []) {
            [$text, $program] = array_shift($queue);
            if (str_starts_with($scenario['target'], $text)) {
                $suffix = substr($scenario['target'], strlen($text));
                if (preg_match('/^[A-Za-z0-9_-]*$/D', $suffix)) {
                    $append = array_map(fn ($chunk) => 'append '.$chunk, str_split($suffix, 12));

                    return [...$program, ...$append] ?: ['upper', 'lower'];
                }
            }
            if (count($program) >= 4) {
                continue;
            }
            foreach (['trim' => trim($text), 'upper' => strtoupper($text), 'lower' => strtolower($text), 'reverse' => implode('', array_reverse(mb_str_split($text)))] as $command => $result) {
                if (! isset($visited[$result])) {
                    $visited[$result] = true;
                    $queue[] = [$result, [...$program, $command]];
                }
            }
        }
        throw new RuntimeException('No text solution found.');
    }

    public function solution(array $stage): array
    {
        if ($stage['template'] === 'choice-quiz') {
            return $stage['version'] === 1 ? ['answer' => $stage['answer']]
                : ['answers' => collect($stage['questions'])->mapWithKeys(fn ($question) => [$question['id'] => match ($question['type'] ?? 'choice') {
                    'blank' => $question['accepted'][0], 'matching' => array_combine(array_column($question['pairs'], 'id'), array_column($question['pairs'], 'id')), default => $question['answer']
                }])->all()];
        }
        if ($stage['template'] === 'command-garden') {
            return ['program' => app(GardenLayout::class)->solution($stage), 'repeat' => $stage['mode'] === 'loop', 'conditional' => $stage['mode'] === 'conditional'];
        }
        $scenario = $stage['scenario'];
        $program = match ($stage['template']) {
            'logic-switchboard' => $this->circuitSolution($scenario),
            'string-forge' => $this->textSolution($scenario),
            'memory-trail' => [...array_fill(0, count($scenario['start']), 'pop'), ...array_map(fn ($value) => 'push '.$value, $scenario['target'])],
            'pixel-studio' => array_map(fn ($pixel) => 'paint '.$pixel, $scenario['pixels']),
            'number-machine' => array_map(fn ($n) => 'add '.$n, $this->increments($scenario['target'] - $scenario['start'])),
            'terminal-quest' => ['cp '.collect($scenario['files'])->firstWhere('content', $scenario['content'])['name'].' '.$scenario['destination'], 'cat '.$scenario['destination']],
            default => [],
        };
        if ($stage['template'] === 'sort-lab') {
            $items = $scenario['items'];
            $sorted = $items;
            sort($sorted);
            foreach ($sorted as $index => $value) {
                if ($items[$index] !== $value) {
                    $position = array_search($value, $items, true);
                    $program[] = 'swap '.($index + 1).' '.($position + 1);
                    [$items[$index], $items[$position]] = [$items[$position], $items[$index]];
                }
            }
        }

        return ['program' => $program];
    }

    private function increments(int $difference): array
    {
        $parts = [];
        while (abs($difference) > 100) {
            $part = $difference < 0 ? -100 : 100;
            $parts[] = $part;
            $difference -= $part;
        }

        return [...$parts, $difference];
    }

    public function entry(array $entry, int $position): array
    {
        $stages = [];
        foreach ($entry['stages'] as $index => $stage) {
            try {
                $solution = $this->solution($stage);
                $valid = $stage['template'] === 'choice-quiz'
                    ? app(QuizAuthoring::class)->succeeds($stage, $solution)
                    : app(GameAssessment::class)->succeeds($stage, $solution);
            } catch (\Throwable) {
                $solution = null;
                $valid = false;
            }
            $briefing = app(LessonBriefings::class)->for($stage);
            $stages[] = ['stage' => $index + 1, 'template' => $stage['template'], 'version' => $stage['version'],
                'topic' => $stage['concept'] ?? $entry['concept'], 'language' => $stage['template'] === 'choice-quiz' ? $entry['concept'] : 'Bounded simulator pseudocode',
                'objective' => $briefing['outcome'], 'task' => $briefing['task'], 'success' => $briefing['success'],
                'hint' => $stage['hint'] ?? 'Use the question explanation after checking your prediction.',
                'example' => $briefing['example'], 'reference_submission' => $solution, 'reference_verified' => $valid, 'reference_status' => $valid ? 'Server verified' : 'Needs manual editorial review; the bounded reference finder did not verify this revision',
                'common_wrong_approach' => 'Checking only an intermediate state, changing instruction order without tracing, or confusing one-based simulator positions with language indexes.',
                'transfer_task' => 'Predict the next state, compare it with the trace, then change one instruction and explain the difference. This is optional reflection, not an independently graded mastery claim.'];
        }

        return ['position' => $position, 'title' => $entry['title'], 'topic' => $entry['concept'],
            'track' => $position <= 25 ? 'Beginner' : 'Intermediate practice',
            'prerequisite' => in_array($position, [1, 26], true) ? 'Independent track entry; no earlier clears granted.' : 'Previous published levels in this track.',
            'assessment_limit' => 'Formative practice; completion verifies the saved objective, not general programming mastery.',
            'stages' => $stages];
    }
}
