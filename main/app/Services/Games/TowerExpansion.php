<?php

namespace App\Services\Games;

/** Original, formative lessons. Source reading and editorial checks are recorded in docs/tower-skill-expansion.md. */
class TowerExpansion
{
    public function entries(): array
    {
        $game = fn (string $slug, array $scenario) => array_replace(config('arcade.'.$slug), ['scenario' => $scenario]);
        $q = $this->choice(...);
        $b = $this->blank(...);
        $m = $this->matching(...);
        $quiz = $this->quiz(...);
        $entry = fn ($title, $concept, $description, $stages) => compact('title', 'concept', 'description', 'stages');

        return [
            ...(require __DIR__.'/TowerData/beginner.php')($entry, $quiz, $q, $b, $m, $game),
            ...(require __DIR__.'/TowerData/intermediate.php')($entry, $quiz, $q, $b, $m, $game),
            ...(require __DIR__.'/TowerData/expert.php')($entry, $quiz, $q, $b, $m),
        ];
    }

    public function track(int $position): ?string
    {
        return match (true) {
            $position >= 51 && $position <= 60 => 'beginner',
            $position >= 61 && $position <= 70 => 'intermediate',
            $position >= 71 && $position <= 80 => 'expert',
            default => null,
        };
    }

    private function quiz(string $title, string $outcome, string $explanation, string $example, array $questions): array
    {
        return app(QuizAuthoring::class)->instance($title, $questions) + ['briefing' => compact('outcome', 'explanation', 'example') + [
            'task' => 'Apply the rule to the new examples below. Predict before checking; use the explanations to revise your reasoning.',
            'success' => 'Every answer must be correct. This checks the stated tasks and does not certify mastery or execute the displayed code.',
        ]];
    }

    /** The first supplied label is correct; rotate its display position so keys are not always first. */
    private function choice(string $id, string $question, array $labels, string $explanation): array
    {
        $rotation = strlen($id) % count($labels);
        $options = array_map(fn ($label, $index) => ['id' => 'o'.$index, 'label' => $label], $labels, array_keys($labels));

        return ['id' => $id, 'type' => 'choice', 'question' => str_replace('\\n', "\n", $question), 'explanation' => $explanation,
            'options' => [...array_slice($options, $rotation), ...array_slice($options, 0, $rotation)], 'answer' => 'o0'];
    }

    private function blank(string $id, string $question, array $accepted, string $explanation): array
    {
        return ['id' => $id, 'type' => 'blank', 'question' => str_replace('\\n', "\n", $question), 'accepted' => $accepted, 'case_sensitive' => false, 'explanation' => $explanation];
    }

    private function matching(string $id, string $question, array $labels, string $explanation): array
    {
        $pairs = [];
        foreach ($labels as $left => $right) {
            $pairs[] = ['id' => 'p'.count($pairs), 'left' => $left, 'right' => $right];
        }

        return compact('id', 'question', 'pairs', 'explanation') + ['type' => 'matching'];
    }
}
