<?php

return [
    'quiz-lab' => ['template' => 'choice-quiz', 'version' => 3, 'title' => 'Quiz Lab', 'concept' => 'Choose, recall and connect', 'questions' => [
        ['id' => 'repeat', 'type' => 'choice', 'question' => 'value = 2; REPEAT 2 TIMES { value = value + 3 }. The final value is 8. Is this trace correct?',
            'options' => [['id' => 'true', 'label' => 'True'], ['id' => 'false', 'label' => 'False']], 'answer' => 'true', 'explanation' => 'The first iteration changes 2 to 5; the second changes 5 to 8. The starting value is not added again.'],
        ['id' => 'blank', 'type' => 'blank', 'question' => 'Complete the pseudocode: ___ score >= 10 THEN unlock_next_level().',
            'accepted' => ['if'], 'case_sensitive' => false, 'explanation' => 'An if statement checks a condition before running its action.'],
        ['id' => 'match', 'type' => 'matching', 'question' => 'Match each concept to its use in a program.', 'pairs' => [
            ['id' => 'loop', 'left' => 'Loop', 'right' => 'Run MOVE RIGHT twice'], ['id' => 'variable', 'left' => 'Variable', 'right' => 'Store the remaining number of moves'],
            ['id' => 'condition', 'left' => 'Condition', 'right' => 'Check whether a crystal is on this tile']], 'explanation' => 'These ideas help you describe a program’s state and behavior.'],
    ]],
];
