<?php

return static function (Closure $entry, Closure $quiz, Closure $q, Closure $b, Closure $m, Closure $game): array {
    return [
        // 51–60: append to Beginner; historical floors 1–50 never move.
        $entry('Decisions at the boundary', 'Inclusive comparisons', 'Compare the boundary itself, not just the values on either side. Predict an if/else result before trying another input.', [
            $quiz('Let the boundary decide', 'Trace comparisons at an exact threshold.', 'The operators < and <= differ when both operands are equal. Only the selected branch executes.', 'Different example: with limit = 5, 5 < limit is false but 5 <= limit is true.', [
                $q('edge', 'Python: score = 10\nif score >= 10: badge = "ready"\nelse: badge = "try"\nWhat is badge?', ['ready', 'try', 'Both values'], 'Equality satisfies >=, so only the ready branch runs.'),
                $b('below', 'For the same program with score = 9, write the badge text without quotes.', ['try'], 'Nine is below ten; execution takes the else branch.'),
            ]),
        ]),
        $entry('Count before you repeat', 'Loop accumulation', 'Follow a counter and an accumulator separately. The loop variable is not necessarily the result you are building.', [
            $quiz('A loop has two stories', 'Trace counter values and an accumulated sum.', 'A loop repeats its body for each selected value. Python range excludes its stop value.', 'Different example: adding each value in range(2, 4) adds 2 and 3, producing 5.', [
                $b('sum', 'Python: total = 0\nfor n in range(1, 5): total += n\nWhat is total?', ['10'], 'The body adds 1 + 2 + 3 + 4.'),
                $q('empty', 'Python: total = 7\nfor n in range(3, 3): total += n\nWhat is total?', ['7', '10', '0'], 'An empty range executes no body instructions, preserving the initial total.'),
            ]),
        ]),
        $entry('Two names, one list', 'Values and aliases', 'Distinguish changing a shared list from changing which list a name refers to. These are original Python reading exercises, not live code execution.', [
            $quiz('Follow the object', 'Distinguish an alias from a shallow list copy.', 'Assigning b = a shares the same list. For these integer-only examples, a.copy() creates a separate list whose later append does not change a.', 'Different example: a = [8]; b = a.copy(); b.append(9) leaves a as [8].', [
                $q('alias', 'Python: a = [1, 2]; b = a; b.append(3)\nWhat is a?', ['[1, 2, 3]', '[1, 2]', '[3]'], 'Both names refer to the same mutable list.'),
                $q('copy', 'Python: a = [1, 2]; b = a.copy(); b.append(3)\nWhat is a?', ['[1, 2]', '[1, 2, 3]', '[]'], 'The new outer list is independent; appending to b leaves a unchanged.'),
            ]),
        ]),
        $entry('Make a clean username', 'Text transformation order', 'Practice a text pipeline, then explain why reversing its steps can change the result. This simulator is local text manipulation, not account editing.', [
            $game('string-forge', ['start' => '  Pilot  ', 'target' => 'PILOT_OK']),
            $quiz('Explain your pipeline', 'Predict text state after each operation.', 'TRIM removes outer whitespace. APPEND adds literal characters at the end; appending before trimming may trap spaces inside the text.', 'Different example: " a " followed by APPEND("x") has an interior space before x, which TRIM cannot remove.', [
                $q('order', 'To transform "  Pilot  " into "PILOT_OK", which sequence works?', ['TRIM, UPPER, APPEND("_OK")', 'UPPER, APPEND("_OK"), TRIM', 'REVERSE, LOWER'], 'Trim the outer spaces before appending the suffix.'),
            ]),
        ]),
        $entry('Coordinates leave a trail', 'Indexed grids', 'Recreate a symmetric signal and connect the displayed column/row coordinates to a precise location.', [
            $game('pixel-studio', ['pixels' => ['1 1 mint', '3 1 mint', '2 2 peach', '1 3 lavender', '3 3 lavender']]),
            $quiz('Locate the center', 'Read the two coordinates without swapping them.', 'In this simulator, the first coordinate is the column and the second is the row; both begin at one.', 'Different example: (3,1) is the rightmost cell in the top row.', [
                $m('coordinates', 'Match the coordinate with its position in the 3 by 3 board.', ['1,3' => 'Bottom left', '2,2' => 'Center', '3,1' => 'Top right'], 'Read the column first, then the row.'),
            ]),
        ]),
        $entry('A function is a contract', 'Inputs and return values', 'Reason about a function through its input and returned value. Calling a function and changing the caller variable are separate operations.', [
            $quiz('Follow the return', 'Distinguish returned values from caller assignments.', 'In these examples, the function computes a new number. The caller changes a variable only if it assigns the result.', 'Different example: twice(4) returns 8; n remains 4 unless n = twice(n) is executed.', [
                $b('compose', 'Python: def plus_two(x): return x + 2\nWhat is plus_two(plus_two(3))?', ['7'], 'The inner call returns 5, and the outer call returns 7.'),
                $q('caller', 'Python: def next_value(x): return x + 1\nn = 6\nnext_value(n)\nWhat is n?', ['6', '7', '0'], 'The return value is unused; no assignment changes n.'),
            ]),
        ]),
        $entry('Equal values still belong', 'Duplicates and ordering', 'A sorted collection can contain equal values. Preserve every occurrence while arranging neighbors in nondecreasing order.', [
            $game('sort-lab', ['items' => [4, 1, 4, 2, 1, 3]]),
            $quiz('Check the whole collection', 'Distinguish sorting from removing duplicates.', 'Ascending order permits equal neighbors. Sorting preserves the input multiset: every value keeps its original count.', 'Different example: sorting [2,1,2] gives [1,2,2], not [1,2].', [
                $q('duplicates', 'Which is the sorted result of [4, 1, 4, 2, 1, 3]?', ['[1, 1, 2, 3, 4, 4]', '[1, 2, 3, 4]', '[1, 1, 2, 4, 3, 4]'], 'Preserve both ones and both fours, and check every neighboring pair.'),
            ]),
        ]),
        $entry('Inspect a release note', 'Virtual CLI verification', 'Use filenames and contents as separate clues. Copy only the requested data and read the destination to verify it.', [
            $game('terminal-quest', ['files' => [['name' => 'old.txt', 'content' => 'Release 1'], ['name' => 'draft.txt', 'content' => 'Release 3 draft'], ['name' => 'approved.txt', 'content' => 'Release 2 approved']], 'destination' => 'release.txt', 'content' => 'Release 2 approved']),
            $quiz('Read the command', 'Identify a command and its arguments.', 'Only ls, cat and cp are supported here. Paths are virtual filenames; none of these commands runs on the host computer.', 'Different example: cp source.txt backup.txt reads source.txt and writes backup.txt.', [
                $m('commands', 'Match each virtual command to its job.', ['ls' => 'List filenames', 'cat release.txt' => 'Inspect the destination', 'cp approved.txt release.txt' => 'Copy approved data'], 'Separate discovery, inspection and copying.'),
            ]),
        ]),
        $entry('Say exactly what you mean', 'Boolean combinations', 'Evaluate AND, OR and NOT by their truth conditions, then connect those rules to an access decision.', [
            $game('logic-switchboard', ['inputs' => [true, false, true], 'gate' => 'and', 'target' => true]),
            $quiz('Combine the conditions', 'Evaluate a composed Boolean rule.', 'A AND B requires both. A OR B requires at least one. NOT reverses a Boolean. Parentheses make the grouping explicit.', 'Different example: (true OR false) AND NOT false evaluates to true.', [
                $q('access', 'Access requires verified AND (owner OR staff). verified=true, owner=false, staff=true. Is access allowed?', ['Yes', 'No', 'Only if owner is true'], 'The inner OR is true; combining it with verified=true gives true.'),
                $q('blocked', 'For the same rule, verified=false, owner=true, staff=true. Is access allowed?', ['No', 'Yes', 'Only when both owner and staff are true'], 'The outer AND fails when verified is false, regardless of the inner OR.'),
            ]),
        ]),
        $entry('Boss: the foundation workshop', 'State, boundaries and testing', 'Bring prediction, precise conditions and boundary tests together. Solve the machine, then explain what would falsify a proposed rule.', [
            $game('number-machine', ['start' => -6, 'target' => 42]),
            $quiz('Defend the result', 'Check a result and its boundary cases.', 'A program that works for one input may fail at an edge. Test the exact boundary and track negative values carefully.', 'Different example: the condition n > 0 excludes zero; n >= 0 includes it.', [
                $b('state', 'A number starts at -6. Add 12, then multiply by 7. What is the final value?', ['42'], 'The intermediate value is 6; multiplying by 7 gives 42.'),
                $q('boundary', 'A rule should accept ages 18 and above. Which test directly detects using > instead of >=?', ['18', '19', '30'], 'Only the exact equality case distinguishes these two comparisons.'),
                $m('habits', 'Match each habit to its purpose.', ['Trace state' => 'Predict intermediate values', 'Test the boundary' => 'Check inclusion at equality', 'Read the objective' => 'Know what success requires'], 'Useful coding habits connect prediction, evidence and a precise requirement.'),
            ]),
        ]),
    ];
};
