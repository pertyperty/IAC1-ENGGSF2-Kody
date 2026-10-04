<?php

return [
    'pixel-studio' => ['template' => 'pixel-studio', 'version' => 1, 'title' => 'Pixel Studio', 'concept' => 'Coordinates & sequences',
        'instructions' => 'Paint the target picture using coordinates. Columns and rows start at 1.', 'hint' => 'Try paint 1 1 mint, then paint 2 2 peach.',
        'learningIdea' => 'Coordinates locate data. A sequence of paint instructions builds a picture.', 'maxCommands' => 12,
        'scenario' => ['pixels' => ['1 1 mint', '2 2 peach']]],
    'number-machine' => ['template' => 'number-machine', 'version' => 1, 'title' => 'Number Machine', 'concept' => 'Variables & arithmetic',
        'instructions' => 'Transform the stored number into the target. Watch how each instruction changes its value.', 'hint' => 'Add 3, then multiply by 2.',
        'learningIdea' => 'A variable stores a value. Each operation updates that value in order.', 'maxCommands' => 12,
        'scenario' => ['start' => 2, 'target' => 10]],
    'sort-lab' => ['template' => 'sort-lab', 'version' => 1, 'title' => 'Sort Lab', 'concept' => 'Arrays & algorithms',
        'instructions' => 'Swap two positions until the numbers are in ascending order. Positions start at 1.', 'hint' => 'Try swap 1 2, then swap 2 3.',
        'learningIdea' => 'An array has indexed positions. A sorting algorithm rearranges them into order.', 'maxCommands' => 12,
        'scenario' => ['items' => [3, 1, 2]]],
    'terminal-quest' => ['template' => 'terminal-quest', 'version' => 1, 'title' => 'Terminal Quest', 'concept' => 'Command-line basics',
        'instructions' => 'Explore your virtual workspace. Copy the correct file to the destination, then read it to confirm its contents.', 'hint' => 'Try ls, cat hello.txt, cp hello.txt release.txt, then cat release.txt.',
        'learningIdea' => 'A command has a name and arguments. You can inspect files and copy data through a CLI.', 'maxCommands' => 12,
        'scenario' => ['files' => [['name' => 'hello.txt', 'content' => 'Hello Kody!']], 'destination' => 'release.txt', 'content' => 'Hello Kody!']],
];
