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
            // 61–70: append to Intermediate; these introduce algorithm selection.
            $entry('Search a sorted signal', 'Binary search', 'Assume random access and ascending sorted input. Discard the half that cannot contain the target, and shrink the remaining interval every time.', [
                $quiz('Narrow the interval', 'Trace one binary-search step and state its precondition.', 'For inclusive bounds lo..hi, mid = floor((lo + hi)/2). If a[mid] is below the target, the next lo is mid + 1.', 'Different example: [2,4,8,12,16], target 12, first mid 2 has value 8, so keep indexes 3..4.', [
                    $b('mid', 'Zero-based indexes: a=[1,3,5,7,9,11,13], lo=0, hi=6. What is the first mid index?', ['3'], 'floor((0+6)/2) is 3, whose value is 7.'),
                    $q('next', 'For that array and target 11, what is the next inclusive search interval?', ['4..6', '0..2', '3..6'], 'The middle value 7 is too small; discard it and all earlier indexes.'),
                    $q('requirement', 'Which input property makes that discard valid?', ['Ascending sorted order', 'All numbers are positive', 'The array length is odd'], 'Sorted order, rather than sign or parity, justifies discarding a half.'),
                ]),
            ]),
            $entry('Two pointers, one sum', 'Monotone search', 'On a sorted array, move the left pointer when the sum is too small and the right pointer when it is too large. Use distinct indexes.', [
                $quiz('Move the useful pointer', 'Justify a two-pointer update.', 'For ascending input, moving left rightward cannot decrease the pair sum. Moving right leftward cannot increase it. Stop when left meets right.', 'Different example: [1,4,6,10], target 14. The sum 11 is small, so move left to 4; 4+10 succeeds.', [
                    $q('move', 'a=[2,4,7,11], target=9. Pointers begin at 2 and 11. Which pointer moves first?', ['Right moves left', 'Left moves right', 'Neither; no pair can exist'], '13 is too large, so reduce the right operand.'),
                    $b('pair', 'For the same array, the successful pair is 2 plus what number?', ['7'], 'After reducing the right pointer from 11 to 7, the sum is 9.'),
                    $q('distinct', 'Why stop when left == right for a pair using distinct indexes?', ['Only one index remains', 'The remaining value must be zero', 'Every pair has already had the same sum'], 'Using that one position twice would violate the distinct-index requirement.'),
                ]),
            ]),
            $entry('Count without losing data', 'Frequency maps', 'A frequency map stores a count per value. An absent key starts at zero; each occurrence increments exactly one count.', [
                $quiz('Build a histogram', 'Distinguish frequency from membership.', 'A set records whether a value occurs. A frequency map also records how often. Counts should sum to the input length.', 'Different example: [5,5,8] produces counts 5:2 and 8:1, whose sum is 3.', [
                    $b('frequency', 'Input [2,3,2,4,3,2]. What count belongs to key 2?', ['3'], 'The value 2 occurs at the first, third and sixth positions.'),
                    $q('structure', 'You must compare whether two inputs contain the same values with the same counts. Which representation retains enough information?', ['Frequency maps', 'Sets alone', 'Only each input length'], 'Sets discard multiplicity, and length alone discards the values.'),
                    $b('total', 'What should the sum of all counts be for that six-element input?', ['6'], 'Every input occurrence contributes one to exactly one count.'),
                ]),
            ]),
            $entry('Protect the stack contract', 'Stack invariants', 'Connect LIFO behavior to expression structure. Before reading a top element, verify that the stack is nonempty.', [
                $game('memory-trail', ['start' => [2, 4, 6], 'target' => [2, 6, 4, 4]]),
                $quiz('Recognize the invalid step', 'Use a stack to reason about nested delimiters.', 'For these single-kind parentheses, push on an opening symbol, pop on a closing symbol, reject a closing symbol on an empty stack, and require an empty final stack.', 'Different example: (()) is valid; (() leaves one unmatched opening symbol.', [
                    $q('invalid', 'Which input first fails by closing an empty stack?', [')(', '(())', '(()'], 'Its very first symbol closes before any opening was stored.'),
                    $q('final', 'Why is checking only the final stack size insufficient?', ['An earlier closing symbol can be unmatched', 'Valid input always leaves one item', 'Every opening must immediately be followed by a closing'], 'The prefix condition matters in addition to the final count.'),
                ]),
            ]),
            $entry('A queue keeps its promise', 'FIFO and data structure choice', 'Contrast queue order with stack order. Select an efficient representation when removals must come from the front.', [
                $quiz('First in, first out', 'Trace a queue and choose a suitable Python container.', 'A FIFO queue removes its oldest item. Python deque supports efficient operations at both ends; deleting the first element of a list shifts the others.', 'Different example: enqueue A, enqueue B, dequeue removes A and leaves B.', [
                    $q('front', 'Enqueue 4, enqueue 8, dequeue once, enqueue 6. Which value is now at the front?', ['8', '6', '4'], 'Removing the oldest value 4 leaves 8 ahead of 6.'),
                    $q('container', 'For repeated append and popleft in Python, which container is designed for this job?', ['collections.deque', 'A list with pop(0)', 'An integer counter alone'], 'deque avoids the repeated front-shifting cost of a list.'),
                    $m('order', 'Match the structure to the removal rule.', ['Stack' => 'Newest stored item first', 'Queue' => 'Oldest stored item first', 'Min-priority queue' => 'Smallest priority first'], 'Removal order is part of the data structure contract.'),
                ]),
            ]),
            $entry('Failure is a test case', 'Boundary testing', 'Testing should include empty input, missing values and threshold equality. A successful example alone cannot establish a general algorithm claim.', [
                $quiz('Find the missing case', 'Choose a test that exposes a concrete defect.', 'A counterexample disproves a universal claim. Trace the stated behavior on the smallest input that can reveal the defect.', 'Different example: code that reads a[0] without checking length fails on an empty list.', [
                    $q('negative', 'A maximum finder starts best=0 and returns best for [-5,-2,-9]. What defect does this reveal?', ['It can return a value absent from an all-negative input', 'It always sorts the input', 'It fails only on duplicate values'], 'No element exceeds zero, so the incorrect initializer remains the result.'),
                    $q('empty', 'A function returns a[0] with no length check. Which test directly reveals the missing precondition?', ['[]', '[0]', '[1,2]'], 'The empty input has no first element.'),
                    $q('proof', 'A sorting function passes ten example tests. What follows?', ['Those examples passed; other inputs still require reasoning or tests', 'It is proven correct for every input', 'Its worst-case runtime is constant'], 'Finite examples provide evidence, not a general proof or runtime bound.'),
                ]),
            ]),
            $entry('Answer many range sums', 'Prefix sums', 'Precompute cumulative totals to avoid resumming every query. Define the empty prefix and interval convention explicitly.', [
                $quiz('Subtract two prefixes', 'Use exclusive-end prefix sums correctly.', 'Define P[0]=0 and P[i+1]=P[i]+a[i]. The zero-based half-open range [l,r) sums to P[r]-P[l]. Building P costs O(n); each sum query is O(1).', 'Different example: a=[3,5,2], P=[0,3,8,10]. The sum for [1,3) is 10-3=7.', [
                    $b('range', 'a=[2,4,1,6], P=[0,2,6,7,13]. What is the sum for [1,4)?', ['11'], 'Subtract P[1]=2 from P[4]=13.'),
                    $b('empty', 'For any valid l, what is the sum for the empty interval [l,l)?', ['0'], 'The same prefix is subtracted from itself.'),
                    $q('cost', 'After O(n) preprocessing, how much time does one static range-sum query take?', ['O(1)', 'O(n)', 'O(n log n)'], 'The query performs two reads and one subtraction, independent of the range length.'),
                ]),
            ]),
            $entry('Recursion needs a way home', 'Base cases and progress', 'A recursive function needs both a stopping case and a measure that moves toward it. Assume nonnegative integer input in these examples.', [
                $quiz('Follow the calls', 'Trace a recurrence and check termination.', 'For f(0)=1 and f(n)=n*f(n-1), each positive n reduces by one. Count the base call when counting total calls.', 'Different example: f(2) calls f(1), which calls f(0); there are three calls and the returned value is 2.', [
                    $b('factorial', 'For that definition, what is f(4)?', ['24'], '4*3*2*1*1 is 24.'),
                    $b('calls', 'How many total calls occur for f(4), including f(0)?', ['5'], 'The arguments are 4,3,2,1,0.'),
                    $q('termination', 'Replacing f(n-1) with f(n+1) for positive n breaks which argument?', ['The measure no longer moves toward the base case', 'Multiplication stops being associative', 'The base case now executes first'], 'The recursive argument increases instead of approaching zero.'),
                ]),
            ]),
            $entry('Count growth, not milliseconds', 'Asymptotic reasoning', 'State the input-size measure and the operation being counted. O-notation describes a bound, not an exact timing on a particular computer.', [
                $quiz('Compare the loops', 'Distinguish linear, quadratic and logarithmic work.', 'Two consecutive n-step loops add their work. An n-step loop nested inside another multiplies it. Repeated halving gives logarithmically many steps.', 'Different example: n + n is 2n, which is O(n), while n*n is O(n^2).', [
                    $m('growth', 'Match each process to a tight asymptotic bound under unit-cost operations.', ['Two consecutive n-iteration loops' => 'Theta(n)', 'Two nested n-iteration loops' => 'Theta(n^2)', 'Halve a positive n until it is at most 1' => 'Theta(log n)'], 'Ignore fixed constant factors, while preserving how work grows.'),
                    $q('assumption', 'A loop makes n calls to a function that itself scans n elements. What is the total worst-case work?', ['Theta(n^2)', 'Theta(n)', 'Theta(1)'], 'Each of the n calls contributes a linear scan.'),
                ]),
            ]),
            $entry('Boss: the strategy observatory', 'Search, representation and evidence', 'Choose a structure based on the operations you need, then challenge a proposed algorithm with boundary cases and a runtime argument.', [
                $quiz('Choose the representation', 'Connect requirements to data and algorithm choices.', 'Sorted arrays support binary search; frequency maps preserve multiplicity; prefix sums answer static sum queries. Their setup costs and assumptions differ.', 'Different example: sorting once can help many searches, but it is not free preprocessing.', [
                    $m('tools', 'Match the requirement to the suitable technique.', ['Many static range sums' => 'Prefix sums', 'Find an index in sorted random-access data' => 'Binary search', 'Compare duplicate counts' => 'Frequency maps'], 'Choose by required operations, not by the name of the technique.'),
                    $q('unsorted', 'A standard binary search is used directly on [9,1,7,3]. What is missing?', ['The sorted-order precondition', 'A positive first element', 'An even number of elements'], 'Without sorted order, discarding a half is unjustified.'),
                ]),
                $quiz('Review a runtime claim', 'Separate preprocessing, queries and correctness evidence.', 'An algorithm claim should name assumptions, count all phases and consider a falsifying input. Faster code with the wrong result is not a solution.', 'Different example: O(n) setup followed by q constant-time queries costs O(n+q).', [
                    $q('total', 'Building prefix sums costs O(n). Answering q queries costs O(1) each. What is the total?', ['O(n+q)', 'O(n*q)', 'O(q) regardless of setup'], 'Add the setup and all query costs.'),
                    $q('counterexample', 'A claimed maximum algorithm initializes best=0. Which input disproves correctness for nonempty integer lists?', ['[-4,-1]', '[0,2]', '[1,1]'], 'For all-negative input it returns zero, which was never present.'),
                ]),
            ]),
            // 71–80: independently enterable advanced algorithm reasoning, not a runtime or credential.
            $entry('Prove the search boundary', 'Loop invariants', 'Assume sorted random-access data and zero-based indexes. Reason about the half-open lower-bound search: all indexes before lo hold values below x, and all indexes at or after hi hold values at least x.', [
                $quiz('Maintain the invariant', 'Connect a boundary update to a loop invariant and termination.', 'Use lo=0, hi=n; while lo<hi, mid=lo+floor((hi-lo)/2). If a[mid]<x set lo=mid+1; otherwise set hi=mid. The candidate interval is [lo,hi), and its length strictly shrinks.', 'Different example: in [1,3,3,8], the first index with value >= 3 is 1, not an arbitrary matching index.', [
                    $b('bound', 'For a=[2,4,4,4,9] and x=4, what lower-bound index does this algorithm return?', ['1'], 'The first qualifying value is at index 1; duplicates to its right do not change the first boundary.'),
                    $q('update', 'When a[mid] >= x, why is hi=mid rather than lo=mid+1?', ['mid may be the first qualifying index', 'Every earlier value must equal x', 'The array is no longer sorted'], 'Keep mid as a possible boundary by excluding the region strictly after it.'),
                    $q('progress', 'With lo<hi, which statement proves termination?', ['The nonnegative integer hi-lo strictly decreases each iteration', 'The target must occur in the array', 'lo always stays at zero'], 'Both updates reduce a bounded integer measure, whether or not the target occurs.'),
                    $b('absent', 'For the same array and x=10, what boundary index is returned?', ['5'], 'No element qualifies; the insertion boundary is n, which is a valid result rather than an array element.'),
                ]),
            ]),
            $entry('One expensive append', 'Amortized analysis', 'Model a dynamic array that doubles capacity when full. Copying existing elements during a resize is expensive, but resizes are geometrically spaced. Count copied old elements, separately from writing new ones.', [
                $quiz('Pay across the sequence', 'Distinguish worst-case single-operation cost from amortized cost.', 'Starting empty with capacity 1, resizing at capacities 1,2,4,... copies that many old elements. Across n appends the geometric sum is O(n), so amortized append cost is O(1); one resizing append is still O(n).', 'Different example: the first five appends copy 1+2+4=7 old elements in total, even though two of those appends require no copying.', [
                    $b('copies', 'Starting empty at capacity 1, how many old elements are copied across eight appends, doubling only when full?', ['7'], 'The resizes copy 1, then 2, then 4; the eighth append fits capacity 8.'),
                    $q('worst', 'What is the cost of one append that resizes an array containing n elements?', ['Theta(n)', 'Theta(1)', 'Theta(log n)'], 'That operation alone must copy all n existing elements.'),
                    $q('amortized', 'What does O(1) amortized append mean under this model?', ['Total work for n appends is O(n)', 'Every append has constant worst-case latency', 'No append copies data'], 'Amortized analysis bounds total work over a sequence, not every individual latency.'),
                ]),
            ]),
            $entry('Find the fewest hops', 'Breadth-first search', 'Use adjacency lists and mark a vertex when it is enqueued. In an unweighted graph, FIFO exploration discovers vertices by nondecreasing edge distance; this does not solve arbitrary weighted shortest paths.', [
                $quiz('Trace the frontier', 'Relate FIFO discovery to unweighted shortest-path distance.', 'BFS initializes source distance 0, discovers an unvisited neighbor at parent distance +1, and enqueues it once. With adjacency lists, one full traversal costs O(V+E).', 'Different example: edges S-A, A-B, S-C, C-B give B distance 2 from S, whichever branch is visited first.', [
                    $b('distance', 'Undirected edges S-A, S-B, A-C, C-T, B-T. What is the shortest edge distance from S to T?', ['2'], 'The path S-B-T uses two edges; the A branch uses three.'),
                    $q('visited', 'Why mark a vertex when enqueuing rather than only when later processing it?', ['To avoid enqueuing it repeatedly through different parents', 'To sort all edge weights', 'To force depth-first traversal'], 'Multiple frontier vertices can point to the same undiscovered neighbor.'),
                    $q('weighted', 'Does ordinary FIFO BFS minimize total weight when edges have arbitrary positive weights?', ['No; the fewest edges can have a larger total weight', 'Yes; positive weights are always equal', 'Only if the graph has no cycles'], 'BFS minimizes edge count; weighted cost needs a suitable weighted-path algorithm.'),
                ]),
            ]),
            $entry('Schedule without a cycle', 'DAG ordering', 'An edge A->B means A must precede B. A topological ordering exists exactly when the directed graph has no directed cycle. Different valid orders may exist.', [
                $quiz('Remove ready work', 'Reason about indegrees and incomplete topological processing.', 'Kahn-style processing repeatedly removes an indegree-zero vertex and reduces its outgoing neighbors. If fewer than V vertices can be processed, a directed cycle remains.', 'Different example: A->C and B->C permit A,B,C or B,A,C; both prerequisites must precede C.', [
                    $q('order', 'Edges A->C, B->C, C->D. Which is a valid topological order?', ['B,A,C,D', 'C,A,B,D', 'A,C,D,B'], 'Both A and B must come before C, and C before D.'),
                    $q('cycle', 'For edges A->B, B->C, C->A, how many vertices initially have indegree zero?', ['0', '1', '3'], 'Every vertex has one incoming edge from another vertex in the cycle.'),
                    $q('partial', 'An algorithm removes some ready vertices, then stalls with unprocessed vertices. What must the remaining directed graph contain?', ['A directed cycle', 'Only isolated vertices', 'No edges'], 'If the remaining graph were acyclic, at least one remaining vertex would have indegree zero.'),
                ]),
            ]),
            $entry('Weights change the promise', 'Dijkstra and counterexamples', 'For the standard settled-once Dijkstra algorithm, nonnegative edge weights justify finalizing the nearest unsettled vertex. A negative edge can invalidate that greedy choice even without a negative cycle.', [
                $quiz('Challenge a greedy settlement', 'Identify the assumptions behind a shortest-path algorithm.', 'Relaxing u->v of weight w proposes dist[u]+w for v. Dijkstra settles a minimum tentative distance; nonnegative remaining edges prevent a later path from reducing that settled value.', 'Different example: S-A=4, S-B=1, B-A=1 gives A distance 2 through B rather than the direct cost 4.', [
                    $b('weighted', 'Directed edges S->A:7, S->B:2, B->A:3, A->T:1, B->T:9. What is the minimum S-to-T cost?', ['6'], 'S-B-A-T costs 2+3+1=6, less than the alternatives.'),
                    $q('negative', 'S->A:2, S->B:5, B->A:-10. A settled-once algorithm finalizes A before B. What goes wrong?', ['A later path through B has cost -5 and improves settled A', 'The graph necessarily has a negative cycle', 'The direct S->A edge has cost -10'], 'A is finalized at 2, but S-B-A has lower cost; this graph has no directed cycle.'),
                    $q('algorithm', 'Which algorithm handles negative edges when relevant negative cycles are absent?', ['Bellman-Ford', 'Ordinary unweighted BFS', 'Settled-once Dijkstra without modifications'], 'Bellman-Ford repeatedly relaxes edges and can also detect reachable negative cycles.'),
                ]),
            ]),
            $entry('When greedy change fails', 'Dynamic programming', 'For unlimited positive integer coin values, define dp[s] as the fewest coins totaling s. Set dp[0]=0 and unreachable states to infinity. Build smaller sums before larger ones.', [
                $quiz('Choose a recurrence', 'Use optimal substructure and expose a greedy counterexample.', 'For s>0, dp[s]=1+min(dp[s-c]) over available coins c<=s with reachable predecessors. A greedy largest-coin choice is not optimal for every denomination set.', 'Different example: coins [1,4,6], target 8. Greedy chooses 6+1+1, while 4+4 uses fewer coins.', [
                    $b('minimum', 'Unlimited coins [1,3,4], target 6. What is the minimum number of coins?', ['2'], '3+3 uses two coins; greedy 4+1+1 uses three.'),
                    $q('base', 'Why must dp[0] be zero?', ['Making sum zero uses no coins', 'Zero is an available coin that can be chosen forever', 'Every amount is initially reachable'], 'It is the valid empty solution from which positive sums can be built.'),
                    $q('unreachable', 'With coins [4,6], what should dp[5] represent?', ['Unreachable', 'Zero coins', 'One coin'], 'No nonnegative combination of 4 and 6 totals 5.'),
                    $q('cost', 'For target T and k coin types, what time does a direct bottom-up implementation use?', ['O(T*k)', 'O(log T)', 'O(k) regardless of T'], 'It checks up to k coins for each of T positive states; the bound depends on numeric target value.'),
                ]),
            ]),
            $entry('Do not reuse the item', 'Zero-one knapsack', 'Each item may be selected at most once. In a one-dimensional capacity DP, update capacities downward so a transition reads values from before the current item was added.', [
                $quiz('Make the update direction matter', 'Detect accidental unbounded reuse in a compressed DP.', 'For one item of weight w and value v, update dp[c]=max(dp[c],dp[c-w]+v) for c from capacity down to w. Ascending updates can reuse the current item.', 'Different example: one item (weight 3,value 5), capacity 6. Ascending updates may create value 10 using the single item twice.', [
                    $b('once', 'One item of weight 2 and value 3, capacity 4, initially all dp entries zero. What is the correct zero-one optimum?', ['3'], 'Only one copy exists, so the value cannot exceed 3.'),
                    $b('ascending', 'For that example, an ascending c=2,3,4 update incorrectly sets dp[4] to what value?', ['6'], 'dp[2] becomes 3, then dp[4] reads that newly updated value and adds 3 again.'),
                    $q('descending', 'Why does descending capacity avoid this reuse?', ['dp[c-w] has not yet incorporated the current item', 'It forbids every combination of different items', 'It sorts the items by value'], 'The smaller-capacity predecessor still belongs to the previous item iteration.'),
                ]),
            ]),
            $entry('A hash promise has assumptions', 'Expected and worst-case bounds', 'State the model before claiming constant-time lookup. In a separate-chaining hash table, many colliding keys can occupy one chain; expected behavior and worst-case behavior differ.', [
                $quiz('Read the small print', 'Separate load assumptions, collisions and equality.', 'Under a suitable hash distribution and controlled load factor, expected chain length stays bounded. With n distinct colliding keys in one chain, a missing-key lookup may inspect all n.', 'Different example: hash(key)=0 sends every key to one bucket, defeating the usual uniform-distribution assumption.', [
                    $q('worst', 'In a separate-chaining table with all n keys in one chain, what is the worst-case missing-key lookup cost?', ['Theta(n)', 'Theta(1)', 'Theta(log n) automatically'], 'A linear chain may require examining every stored key.'),
                    $q('collision', 'Two distinct keys have the same hash. What must the table still do?', ['Compare keys for equality within the collision handling scheme', 'Treat them as the same key', 'Delete both keys'], 'A hash collision is not proof that the keys are equal.'),
                    $q('expected', 'Which statement is honest for a suitable hash table with bounded load?', ['Expected O(1) lookup under its hashing assumptions', 'Guaranteed O(1) for every adversarial input', 'No collisions can ever occur'], 'Expected bounds require assumptions and do not eliminate collisions or all worst cases.'),
                ]),
            ]),
            $entry('Sort records without losing intent', 'Stability and memory tradeoffs', 'A stable sort preserves the relative order of equal-key records. Runtime, stability and auxiliary space are separate properties; name the concrete implementation when comparing them.', [
                $quiz('Preserve the tie order', 'Choose a sorting strategy with explicit tradeoffs.', 'Assume standard array merge sort with O(n) auxiliary storage and a stable merge that chooses the left record on equal keys. It runs in O(n log n). Standard in-place heapsort uses O(1) auxiliary array storage but is not stable.', 'Different example: sorting (2,A),(1,B),(2,C) by key stably yields (1,B),(2,A),(2,C).', [
                    $q('stable', 'Input records (3,A),(1,B),(3,C),(1,D). Which result is stable when sorted only by numeric key?', ['(1,B),(1,D),(3,A),(3,C)', '(1,D),(1,B),(3,C),(3,A)', '(3,A),(3,C),(1,B),(1,D)'], 'Keep B before D and A before C while ordering the keys.'),
                    $q('tie', 'During that stable merge, equal keys occur at the two run heads. Which record is taken first?', ['The left-run record', 'The right-run record', 'Whichever has the longer label'], 'The left run contained earlier equal-key records in the original order.'),
                    $q('tradeoff', 'Stable ties are mandatory and O(n) extra array storage is acceptable. Which stated implementation meets this requirement?', ['The stable merge sort described above', 'Standard in-place heapsort without stability metadata', 'Any sorting algorithm, because sorted implies stable'], 'Sorted order alone does not guarantee tie-order preservation.'),
                ]),
            ]),
            $entry('Boss: defend the algorithm', 'Algorithm selection and counterexamples', 'Combine graph assumptions, dynamic-programming state and falsifying examples. These are advanced formative reasoning tasks, not live language execution or an Expert certification.', [
                $quiz('Choose a valid guarantee', 'Match graph structure to a justified path algorithm.', 'Fewest hops differs from least weight. DAG relaxation in topological order supports negative weights because no cycle exists. Dijkstra requires nonnegative weights for its standard greedy settlement guarantee.', 'Different example: a direct edge of weight 20 uses fewer hops than a two-edge path of total weight 3, but has greater cost.', [
                    $m('paths', 'Match each situation to an appropriate shortest-path method.', ['Unweighted graph; fewest edges' => 'BFS', 'Nonnegative weighted graph' => 'Dijkstra', 'Weighted DAG, possibly negative edges' => 'Topological-order relaxation', 'Negative edges; no reachable negative cycle' => 'Bellman-Ford'], 'Choose by assumptions rather than applying one traversal to every graph.'),
                    $q('cycle', 'A reachable negative cycle can also reach target T. What happens to the minimum walk cost to T?', ['There is no finite minimum: repeated cycles lower it indefinitely', 'BFS returns the correct weighted minimum', 'The minimum must be zero'], 'Repeatedly traversing the negative cycle reduces cost without bound before reaching T.'),
                ]),
                $quiz('State, cost and disproof', 'Audit a proposed solution through state, complexity and a counterexample.', 'A DP state must retain the information needed by future choices. Resource compression must preserve dependencies. A universal greedy claim needs proof; one counterexample is enough to disprove it.', 'Different example: knowing only the number of selected items may lose their total weight, which affects later capacity decisions.', [
                    $q('greedy', 'Coins [1,5,7], target 10. Which comparison disproves always choosing the largest affordable coin?', ['7+1+1+1 versus 5+5', '5+5 versus 5+5', '1+1 versus 7'], 'The greedy solution uses four coins; a feasible alternative uses only two.'),
                    $q('reuse', 'A zero-one knapsack implementation updates capacity upward for each item. Which risk must a reviewer check?', ['The current item can be reused within the same iteration', 'Every state is guaranteed to use no items', 'All negative graph edges are removed'], 'Compression changed the dependency order and may turn zero-one choices into unbounded choices.'),
                    $q('bound', 'A coin DP costs O(T*k), where T is an integer target encoded in binary. Why is this called pseudopolynomial?', ['Its bound depends on target value, which can be exponential in its bit length', 'It always runs in O(log T)', 'It uses no exact arithmetic'], 'The numeric value T can be much larger than the number of bits needed to represent it.'),
                ]),
            ]),
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
