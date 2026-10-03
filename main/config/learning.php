<?php

return [
    // Built-in practice instances. Creator publication will own persisted instances.
    'modules' => [
        'sequences' => ['title' => 'First steps', 'concept' => 'Sequences', 'description' => 'Give your little explorer instructions. Discover why their order matters.', 'icon' => '↗', 'color' => 'mint', 'duration' => '3 min'],
        'loops' => ['title' => 'The loop trail', 'concept' => 'Loops', 'description' => 'Spot a pattern. Build it once, repeat it, and take the clever way home.', 'icon' => '↻', 'color' => 'peach', 'duration' => '5 min'],
        'conditions' => ['title' => 'Crystal collector', 'concept' => 'Conditions', 'description' => 'If there’s a crystal, collect it. Make your program respond to its world.', 'icon' => '◇', 'color' => 'lavender', 'duration' => '5 min'],
    ],
    'quizzes' => [
        'sequences' => ['template' => 'choice-quiz', 'version' => 1, 'title' => 'A quick idea check', 'question' => 'What happens when you change the order of a program’s instructions?', 'options' => [['id' => 'order', 'label' => 'It can change what the program does.'], ['id' => 'same', 'label' => 'The result is always the same.']], 'answer' => 'order', 'explanation' => 'A sequence runs in order. Move first and turn second can lead somewhere different from turn first and move second.'],
        'loops' => ['template' => 'choice-quiz', 'version' => 1, 'title' => 'A quick idea check', 'question' => 'Why use a loop?', 'options' => [['id' => 'repeat', 'label' => 'To repeat a useful set of instructions.'], ['id' => 'skip', 'label' => 'To skip all instructions.']], 'answer' => 'repeat', 'explanation' => 'A loop repeats a pattern. It lets you describe repeated work without copying the same instructions.'],
        'conditions' => ['template' => 'choice-quiz', 'version' => 1, 'title' => 'A quick idea check', 'question' => 'With “if a crystal is here, collect it”, what happens on an empty tile?', 'options' => [['id' => 'skip', 'label' => 'The collection action is skipped.'], ['id' => 'stop', 'label' => 'The whole program must stop.']], 'answer' => 'skip', 'explanation' => 'A condition chooses whether to run an action. A false crystal check skips collecting, while the rest of the program continues.'],
    ],
    'instances' => [
        'sequences' => [
            'template' => 'command-garden', 'version' => 1, 'title' => 'Logic Garden', 'concept' => 'Sequences',
            'instructions' => 'Tap the arrows to build a path. Then run your code and guide Kody to the flag.',
            'width' => 5, 'height' => 4, 'start' => [0, 2], 'goal' => [4, 1],
            'path' => [[0, 2], [1, 2], [2, 2], [2, 1], [3, 1], [4, 1]], 'crystals' => [],
            'maxCommands' => 12, 'mode' => 'sequence', 'hint' => 'Try right, right, up, right, right. Order matters!',
            'learningIdea' => 'That’s a sequence: a program follows your instructions in order. Changing the order changes the result.',
        ],
        'loops' => [
            'template' => 'command-garden', 'version' => 1, 'title' => 'The Loop Trail', 'concept' => 'Loops',
            'instructions' => 'Find a repeating pattern. Use at most three arrows and repeat your program twice.',
            'width' => 5, 'height' => 4, 'start' => [0, 3], 'goal' => [4, 1],
            'path' => [[0, 3], [1, 3], [1, 2], [2, 2], [3, 2], [3, 1], [4, 1]], 'crystals' => [],
            'maxCommands' => 3, 'mode' => 'loop', 'hint' => 'Right, up, right makes one pattern. Tick repeat to use it twice.',
            'learningIdea' => 'That’s a loop: repeat a useful set of instructions instead of writing the same steps again.',
        ],
        'conditions' => [
            'template' => 'command-garden', 'version' => 1, 'title' => 'Crystal Collector', 'concept' => 'Conditions',
            'instructions' => 'Follow the path and collect both crystals. Add a rule that checks for a crystal after each move.',
            'width' => 5, 'height' => 4, 'start' => [0, 2], 'goal' => [4, 1],
            'path' => [[0, 2], [1, 2], [2, 2], [2, 1], [3, 1], [4, 1]], 'crystals' => [[1, 2], [3, 1]],
            'maxCommands' => 12, 'mode' => 'conditional', 'hint' => 'Tick the crystal rule. Then try right, right, up, right, right.',
            'learningIdea' => 'That’s a condition: IF a crystal is here, collect it. On other tiles, that action is skipped.',
        ],
    ],
];
