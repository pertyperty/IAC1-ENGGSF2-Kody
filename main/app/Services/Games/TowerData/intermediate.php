<?php

return static function (Closure $entry, Closure $quiz, Closure $q, Closure $b, Closure $m, Closure $game): array {
    return [
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
    ];
};
