<?php

namespace App\Services\Games;

class LessonBriefings
{
    public function for(array $game): array
    {
        $scenario = $game['scenario'] ?? [];
        $mode = $game['mode'] ?? 'sequence';
        [$outcome, $explanation, $example, $task, $success] = match ($game['template']) {
            'command-garden' => [
                match ($mode) {
                    'loop' => 'Trace a repeated sequence and identify the current iteration.', 'conditional' => 'Predict when an IF action runs and when it is skipped.', default => 'Explain how ordered movement instructions change a position.'
                },
                'Instructions run from top to bottom. Columns increase to the right; rows increase downward. Coordinates shown to you start at 1. '.match ($mode) {
                    'loop' => 'REPEAT runs the entire chosen body exactly twice.', 'conditional' => 'With the crystal rule enabled, IF is checked after every successful move.', default => 'Each MOVE changes one coordinate by one tile.'
                },
                match ($mode) {
                    'loop' => 'Different example: REPEAT 2 TIMES { MOVE RIGHT } moves two columns right, if both tiles are on the path.', 'conditional' => 'Different example: after a MOVE onto an empty tile, IF CRYSTAL HERE is false. COLLECT is skipped; movement continues.', default => 'Different example: from (1,1), MOVE RIGHT then MOVE DOWN ends at (2,2), if those tiles are on the path.'
                },
                'Reach column '.($game['goal'][0] + 1).', row '.($game['goal'][1] + 1).(count($game['crystals']) ? ' and collect all '.count($game['crystals']).' crystals' : '').' without leaving the path.',
                'Finish on the flag with no crystals remaining, using at most '.$game['maxCommands'].' body instructions.'.($mode === 'loop' ? ' Enable repeat: the body must run twice.' : ''),
            ],
            'pixel-studio' => ['Use column and row indexes to change specific cells.', 'PAINT changes one cell. Columns and rows are numbered 1–3; blank target cells must remain blank.', 'Different example: PAINT(3,2,peach) changes column 3, row 2; it does not paint the entire row.', 'Reproduce the target picture, including its blank cells.', 'The final painted cells and colors must match the target exactly.'],
            'number-machine' => ['Trace assignments to a variable and predict its next value.', 'A variable stores one value. Add, subtract and multiply replace that value in instruction order.', 'Different example: value = 4; value = value + 2; value = value * 3 gives 18. Reversing these operations gives 14.', 'Transform '.$scenario['start'].' into '.$scenario['target'].'. Any supported sequence reaching the target is valid.', 'The final value must equal '.$scenario['target'].'. This checks the result, not a prescribed arithmetic strategy.'],
            'sort-lab' => ['Track indexed values through swaps and check ascending order.', 'Positions start at 1 in this simulator. SWAP exchanges two existing positions and preserves the values.', 'Different example: [3,1,2] becomes [1,3,2] after SWAP(1,2). Compare each neighboring pair to check order.', 'Rearrange the displayed values into ascending order.', 'Every neighboring left value must be no greater than its right value. No named sorting algorithm is required.'],
            'terminal-quest' => ['Explain a command and its arguments while inspecting and copying data.', 'ls lists virtual files; cat NAME reads one; cp SOURCE DESTINATION copies its contents. This is a bounded simulator, not your computer or a general shell.', 'Different example: cat example.txt inspects a virtual file; cp example.txt copy.txt creates a copy. Names alone do not prove which content a file contains.', 'Create '.$scenario['destination'].' with the requested contents, then inspect that destination using cat.', 'The destination must have the requested contents and the last successful inspection must verify it.'],
            'logic-switchboard' => ['Evaluate a Boolean expression and relate inputs to its truth value.', 'AND needs all inputs true; OR needs at least one; XOR is true for an odd number of true inputs. FLIP negates one input.', 'Different example: TRUE AND FALSE is FALSE; TRUE XOR TRUE is FALSE. Flipping a switch changes the expression inputs.', 'Set the '.$scenario['gate'].' gate output to '.($scenario['target'] ? 'TRUE' : 'FALSE').'.', 'The final Boolean output must match the target; intermediate lamp changes do not complete the task.'],
            'string-forge' => ['Predict how a sequence of text transformations changes a string.', 'UPPER/LOWER change case, REVERSE reverses characters, TRIM removes outer spaces, and APPEND adds literal text. Case and order matter.', 'Different example: "  abc  " becomes "cba" after TRIM then REVERSE. APPEND("_ok") would then give "cba_ok".', 'Transform the starting text into the exact target text.', 'All final characters, spaces and letter case must match the target.'],
            'memory-trail' => ['Trace stack operations using last-in, first-out state.', 'The rightmost value is TOP. PUSH adds a value, POP removes TOP, DUPLICATE copies TOP, and SWAP exchanges the top two. Capacity is six.', 'Different example: [2,5] becomes [2] after POP; PUSH(7) then gives [2,7]. Popping an empty stack is invalid.', 'Build the target stack without exceeding capacity or using invalid operations.', 'The complete stack must match the target, from bottom to top.'],
            default => ['Read and predict a program state.', 'Read each instruction in order and check the notation or language named in the question.', 'Different example: x starts at 2; adding 1 makes x equal 3.', 'Answer the practice questions and investigate their explanations.', 'Every answer must satisfy the saved quiz key. Practice keys are formative feedback, not a secure examination.'],
        };

        return compact('outcome', 'explanation', 'example', 'task', 'success');
    }

    public function hint(array $game): string
    {
        $scenario = $game['scenario'] ?? [];

        return match ($game['template']) {
            'number-machine' => 'Start from '.$scenario['start'].' and work toward '.$scenario['target'].'. Predict the next value before choosing add, subtract or multiply; operands must be integers from -100 to 100.',
            'terminal-quest' => 'Use ls to discover the actual filenames. Inspect candidates with cat, then copy the matching contents to '.$scenario['destination'].' and read that destination.',
            'pixel-studio' => 'Compare one target cell at a time. Its first number is the column; the second is the row. Leave blank target cells unpainted.',
            'sort-lab' => 'Find the smallest out-of-place value. Swap existing positions, then check each neighboring pair; a sorted endpoint alone is not enough.',
            'command-garden' => 'Trace the path one move at a time. '.(($game['mode'] ?? '') === 'loop' ? 'Find a body that works twice, using at most '.$game['maxCommands'].' moves.' : 'The goal requires all '.count($game['crystals']).' crystals to be collected.'),
            default => $game['hint'],
        };
    }
}
