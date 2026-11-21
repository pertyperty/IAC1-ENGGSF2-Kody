<?php

// Practice-only variations. References are test data and never included in HTTP game instances.
return [
    'pixel-studio' => [
        'challenge' => 'pixel-addresses',
        'warmup' => ['title' => 'Light the corners', 'task' => 'Paint the two target cells. Read column first, then row.', 'hint' => 'The two coordinates are different even though both use 1 and 3.', 'scenario' => ['pixels' => ['1 3 mint', '3 1 peach']], 'limit' => 2, 'reference' => ['program' => ['paint 1 3 mint', 'paint 3 1 peach']]],
        'stretch' => ['title' => 'Rebuild the beacon', 'task' => 'Match every colored cell in 8 instructions. Keep the center blank.', 'hint' => 'Scan a row at a time. Compare both coordinates before changing a cell.', 'scenario' => ['pixels' => ['1 1 lavender', '2 1 mint', '3 1 peach', '1 2 peach', '3 2 lavender', '1 3 mint', '2 3 peach', '3 3 mint']], 'limit' => 8, 'reference' => ['program' => ['paint 1 1 lavender', 'paint 2 1 mint', 'paint 3 1 peach', 'paint 1 2 peach', 'paint 3 2 lavender', 'paint 1 3 mint', 'paint 2 3 peach', 'paint 3 3 mint']]],
    ],
    'number-machine' => [
        'challenge' => 'number-order',
        'warmup' => ['title' => 'Charge the battery', 'task' => 'Turn 3 into 10 in 2 instructions. Choose the order carefully.', 'hint' => 'Doubling after adding also doubles the added amount.', 'scenario' => ['start' => 3, 'target' => 10], 'commands' => ['add 2', 'multiply 2'], 'limit' => 2, 'reference' => ['program' => ['add 2', 'multiply 2']]],
        'stretch' => ['title' => 'Stabilize the reactor', 'task' => 'Turn 3 into 43 in at most 5 instructions using the available operations.', 'hint' => 'Work backward: which operation could produce 43 from a smaller number?', 'scenario' => ['start' => 3, 'target' => 43], 'commands' => ['add 2', 'subtract 1', 'multiply 3'], 'limit' => 5, 'reference' => ['program' => ['add 2', 'multiply 3', 'multiply 3', 'subtract 1', 'subtract 1']]],
    ],
    'sort-lab' => [
        'challenge' => 'sort-indexes',
        'warmup' => ['title' => 'Find the misplaced value', 'task' => 'Sort [3, 2, 1] using only one swap.', 'hint' => 'A swap can place two values correctly at once.', 'scenario' => ['items' => [3, 2, 1]], 'limit' => 1, 'reference' => ['program' => ['swap 1 3']]],
        'stretch' => ['title' => 'Untangle the array', 'task' => 'Sort all six values in at most 5 swaps. Every value starts in the wrong position.', 'hint' => 'Track positions after every swap. A number may move more than once.', 'scenario' => ['items' => [6, 1, 2, 3, 4, 5]], 'limit' => 5, 'reference' => ['program' => ['swap 1 2', 'swap 2 3', 'swap 3 4', 'swap 4 5', 'swap 5 6']]],
    ],
    'terminal-quest' => [
        'challenge' => 'file-investigation',
        'warmup' => ['title' => 'Make a verified backup', 'task' => 'Copy the file containing ready to backup.txt, then read backup.txt.', 'hint' => 'cp takes the source name first and destination name second. cat verifies the copy.', 'scenario' => ['files' => [['name' => 'note.txt', 'content' => 'ready']], 'destination' => 'backup.txt', 'content' => 'ready'], 'limit' => 4, 'reference' => ['program' => ['cat note.txt', 'cp note.txt backup.txt', 'cat backup.txt']]],
        'stretch' => ['title' => 'Investigate the archive', 'task' => 'Find the file containing approved-v2. Copy it to release.txt and verify the result. Names can mislead.', 'hint' => 'Inspect content rather than trusting a filename. Reading a source is not verification of a destination.', 'scenario' => ['files' => [['name' => 'release-old.txt', 'content' => 'approved-v1'], ['name' => 'approved.txt', 'content' => 'draft'], ['name' => 'notes.txt', 'content' => 'approved-v2'], ['name' => 'backup.txt', 'content' => 'outdated']], 'destination' => 'release.txt', 'content' => 'approved-v2'], 'limit' => 7, 'reference' => ['program' => ['ls', 'cat release-old.txt', 'cat approved.txt', 'cat notes.txt', 'cp notes.txt release.txt', 'cat release.txt']]],
    ],
    'logic-switchboard' => [
        'challenge' => 'logic-decision',
        'warmup' => ['title' => 'Complete the circuit', 'task' => 'Make both AND inputs TRUE using one flip.', 'hint' => 'AND needs every input. One input is already TRUE.', 'scenario' => ['inputs' => [true, false], 'gate' => 'and', 'target' => true], 'limit' => 1, 'reference' => ['program' => ['flip 2']]],
        'stretch' => ['check' => ['question' => 'TRUE OR FALSE OR FALSE evaluates to what?', 'options' => ['a' => 'TRUE: one input is enough', 'b' => 'FALSE: all inputs must be TRUE', 'c' => 'It depends on input order'], 'answer' => 'a', 'explanation' => 'OR is TRUE if at least one input is TRUE. It is FALSE only when every input is FALSE.'], 'title' => 'Silence every signal', 'task' => 'Make this OR output FALSE in at most 4 flips.', 'hint' => 'OR stays TRUE while even one input remains TRUE. Compare that with AND.', 'scenario' => ['inputs' => [true, true, true, true], 'gate' => 'or', 'target' => false], 'limit' => 4, 'reference' => ['program' => ['flip 1', 'flip 2', 'flip 3', 'flip 4']]],
    ],
    'string-forge' => [
        'challenge' => 'text-pipeline',
        'warmup' => ['title' => 'Clean the label', 'task' => 'Change " hi " into "HI" in 2 transformations.', 'hint' => 'Spaces are characters too. UPPER does not remove them.', 'scenario' => ['start' => ' hi ', 'target' => 'HI'], 'commands' => ['trim', 'upper', 'reverse'], 'limit' => 2, 'reference' => ['program' => ['trim', 'upper']]],
        'stretch' => ['title' => 'Encode the dispatch', 'task' => 'Change " trace " into "KO_ECART_v2" in 5 transformations. Preserve the lowercase final suffix.', 'hint' => 'Think about which operations would also change a suffix already appended.', 'scenario' => ['start' => ' trace ', 'target' => 'KO_ECART_v2'], 'commands' => ['append _OK', 'reverse', 'lower', 'trim', 'upper', 'append _v2'], 'limit' => 5, 'reference' => ['program' => ['trim', 'upper', 'append _OK', 'reverse', 'append _v2']]],
    ],
    'memory-trail' => [
        'challenge' => 'stack-recovery',
        'warmup' => ['title' => 'Remove the extra item', 'task' => 'Change [2, 5] into [2]. Only the top can be removed.', 'hint' => 'POP removes TOP. The bottom stays in place.', 'scenario' => ['start' => [2, 5], 'target' => [2]], 'commands' => ['pop', 'duplicate', 'swap'], 'limit' => 1, 'reference' => ['program' => ['pop']]],
        'stretch' => ['title' => 'Repair the cargo stack', 'task' => 'Change [1, 9, 3, 8] into [1, 3, 3, 5] in at most 5 operations.', 'hint' => 'Remove the obstructing top first. Bring unwanted values to TOP before removing them.', 'scenario' => ['start' => [1, 9, 3, 8], 'target' => [1, 3, 3, 5]], 'commands' => ['push 5', 'pop', 'duplicate', 'swap'], 'limit' => 5, 'reference' => ['program' => ['pop', 'swap', 'pop', 'duplicate', 'push 5']]],
    ],
    'sequences' => [
        'challenge' => 'route-planning',
        'warmup' => ['title' => 'First delivery', 'task' => 'Reach the flag in 3 moves. Trace the corner before running.', 'hint' => 'Move across the bottom, then take the turn.', 'layout' => ['start' => [0, 2], 'goal' => [2, 1], 'path' => [[0, 2], [1, 2], [2, 2], [2, 1]], 'crystals' => []], 'limit' => 3, 'reference' => ['program' => ['right', 'right', 'up']]],
        'stretch' => ['title' => 'Navigate the detour', 'task' => 'Reach the flag within 8 moves. Your route includes a turn to the left.', 'hint' => 'A path toward the flag can temporarily move away from it.', 'layout' => ['start' => [1, 3], 'goal' => [4, 0], 'path' => [[1, 3], [0, 3], [0, 2], [0, 1], [1, 1], [2, 1], [3, 1], [3, 0], [4, 0]], 'crystals' => []], 'limit' => 8, 'reference' => ['program' => ['left', 'up', 'up', 'right', 'right', 'right', 'up', 'right']]],
    ],
    'loops' => [
        'challenge' => 'repeat-pattern',
        'warmup' => ['title' => 'Repeat one move', 'task' => 'Reach the flag with a one-move body repeated twice.', 'hint' => 'The repeat rule runs the entire body twice.', 'layout' => ['start' => [0, 2], 'goal' => [2, 2], 'path' => [[0, 2], [1, 2], [2, 2]], 'crystals' => []], 'limit' => 1, 'reference' => ['program' => ['right'], 'repeat' => true]],
        'stretch' => ['title' => 'Repeat around the bend', 'task' => 'Find a four-move body that reaches the flag when repeated twice.', 'hint' => 'The same moves must work from the start of both iterations.', 'layout' => ['start' => [0, 2], 'goal' => [4, 2], 'path' => [[0, 2], [0, 1], [1, 1], [2, 1], [2, 2], [3, 1], [4, 1], [4, 2]], 'crystals' => []], 'limit' => 4, 'reference' => ['program' => ['up', 'right', 'right', 'down'], 'repeat' => true]],
    ],
    'conditions' => [
        'challenge' => 'conditional-route',
        'warmup' => ['title' => 'Collect on the way', 'task' => 'Reach the flag and collect one crystal. Enable the collection rule.', 'hint' => 'The same IF rule runs after each move, including an empty tile.', 'layout' => ['start' => [0, 2], 'goal' => [2, 2], 'path' => [[0, 2], [1, 2], [2, 2]], 'crystals' => [[1, 2]]], 'limit' => 2, 'reference' => ['program' => ['right', 'right'], 'conditional' => true]],
        'stretch' => ['title' => 'Collect through the detour', 'task' => 'Collect three crystals and finish at the flag within 9 moves.', 'hint' => 'Not every move collects a crystal. Trace where the IF rule is TRUE.', 'layout' => ['start' => [0, 3], 'goal' => [4, 0], 'path' => [[0, 3], [1, 3], [2, 3], [2, 2], [1, 2], [1, 1], [2, 1], [3, 1], [4, 1], [4, 0]], 'crystals' => [[2, 3], [1, 1], [4, 1]]], 'limit' => 9, 'reference' => ['program' => ['right', 'right', 'up', 'left', 'up', 'right', 'right', 'right', 'up'], 'conditional' => true]],
    ],
];
