<?php

return [
    'quiz-lab' => ['template' => 'choice-quiz', 'version' => 3, 'title' => 'Quiz Lab', 'concept' => 'Choose, recall and connect', 'questions' => [
        ['id' => 'repeat', 'type' => 'choice', 'question' => 'A loop repeats instructions. True or false?',
            'options' => [['id' => 'true', 'label' => 'True'], ['id' => 'false', 'label' => 'False']], 'answer' => 'true', 'explanation' => 'Loops describe repeated work.'],
        ['id' => 'blank', 'type' => 'blank', 'question' => 'An ___ statement chooses an action when a condition is true.',
            'accepted' => ['if'], 'case_sensitive' => false, 'explanation' => 'An if statement checks a condition before running its action.'],
        ['id' => 'match', 'type' => 'matching', 'question' => 'Connect each idea to what it does.', 'pairs' => [
            ['id' => 'loop', 'left' => 'Loop', 'right' => 'Repeats instructions'], ['id' => 'variable', 'left' => 'Variable', 'right' => 'Names a stored value'],
            ['id' => 'condition', 'left' => 'Condition', 'right' => 'Checks whether a statement is true']], 'explanation' => 'These ideas help you describe a program’s state and behavior.'],
    ]],
];
