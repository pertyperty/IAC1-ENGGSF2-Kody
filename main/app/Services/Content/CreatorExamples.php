<?php

namespace App\Services\Content;

class CreatorExamples
{
    public function fields(string $slug): array
    {
        $lesson = config('creator-examples')[$slug];
        if ($slug === 'choice-quiz') {
            $quiz = config('learning.quizzes.sequences');

            return $lesson + ['type' => 'Interactive', 'assessment_kind' => 'quiz',
                'quiz_title' => $quiz['title'], 'quiz_question' => $quiz['question'],
                'quiz_a' => $quiz['options'][0]['label'], 'quiz_b' => $quiz['options'][1]['label'],
                'quiz_answer' => 'a', 'quiz_explanation' => $quiz['explanation']];
        }

        $game = (config('learning.instances') + config('arcade'))[$slug];

        return $lesson + ['type' => 'Interactive', 'assessment_kind' => 'game', 'game_preset' => $slug,
            'game_title' => $game['title'], 'game_instructions' => $game['instructions'],
            'game_hint' => $game['hint'], 'game_learning_idea' => $game['learningIdea']];
    }
}
