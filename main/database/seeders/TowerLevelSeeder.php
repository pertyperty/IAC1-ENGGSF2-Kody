<?php

namespace Database\Seeders;

use App\Models\TowerLevel;
use App\Models\TowerRevision;
use App\Services\Games\LessonBriefings;
use App\Services\Games\QuizAuthoring;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TowerLevelSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            DB::select("SELECT pg_advisory_xact_lock(hashtext('kody_tower_seed'))");
            foreach ($this->curriculum() as $index => $entry) {
                if (TowerLevel::where('position', $index + 1)->exists()) {
                    continue;
                }
                $level = TowerLevel::create(['position' => $index + 1]);
                $revision = TowerRevision::create($entry + ['level_id' => $level->id, 'number' => 1,
                    'source_notes' => 'Original Kody curriculum and scenarios, editorial revision 2026-10-18. Editorial learning material; not copied course or repository content.']);
                $level->update(['current_revision_id' => $revision->id]);
            }
        });
    }

    public function curriculum(): array
    {
        $briefings = app(LessonBriefings::class);

        return array_map(function (array $entry) use ($briefings): array {
            $entry['stages'] = array_map(function (array $stage) use ($briefings): array {
                if ($stage['template'] !== 'choice-quiz') {
                    $stage['instructions'] = $briefings->for($stage)['task'];
                    $stage['hint'] = $briefings->hint($stage);
                }

                return $stage;
            }, $entry['stages']);
            $entry['description'] = implode(' ', array_unique(array_map(fn ($stage) => $briefings->for($stage)['outcome'], $entry['stages'])));
            if ($entry['concept'] === 'Foundation mastery') {
                $entry['concept'] = 'Foundation synthesis';
            }

            return $entry;
        }, $this->legacyCurriculum());
    }

    // Retained only to recognize untouched original seed revisions when suggesting an update.
    public function legacyCurriculum(): array
    {
        $garden = fn (string $slug) => config('learning.instances.'.$slug);
        $arcade = fn (string $slug, array $scenario) => array_replace(config('arcade.'.$slug), ['scenario' => $scenario]);
        $quiz = function (string $title, array $questions): array {
            $items = array_map(fn ($item, $index) => ['id' => 'q'.($index + 1), 'question' => str_replace('\\n', "\n", $item[0]),
                'options' => [['id' => 'a', 'label' => $item[$index % 2 === 0 ? 1 : 2]], ['id' => 'b', 'label' => $item[$index % 2 === 0 ? 2 : 1]]], 'answer' => $index % 2 === 0 ? 'a' : 'b', 'explanation' => $item[3]], $questions, array_keys($questions));

            return app(QuizAuthoring::class)->instance($title, $items);
        };
        $entry = fn ($title, $concept, $stages) => compact('title', 'concept', 'stages') + ['description' => 'Explore '.$concept.' through a hands-on puzzle. Plan, experiment and explain your result.'];
        $terminal = fn (int $count, string $destination) => $arcade('terminal-quest', ['files' => array_map(fn ($n) => ['name' => 'note'.$n.'.txt', 'content' => 'Checkpoint '.$n], range(1, $count)), 'destination' => $destination, 'content' => 'Checkpoint '.$count]);
        $logic = [['What does a sequence describe?', 'Instructions executed in order', 'Instructions always executed simultaneously', 'Order determines how a program changes its state.'],
            ['A loop runs the same body twice. How many times is each body instruction executed?', 'Two', 'One', 'Each repetition executes the instructions in the loop body.'],
            ['An if condition is false. What happens to its body?', 'It is skipped', 'It always runs', 'The body runs only when the condition is true.']];
        $conditions = array_replace($garden('conditions'), ['crystals' => [[1, 2], [2, 1], [3, 1]], 'title' => 'Three crystal decisions']);

        return [
            $entry('First steps', 'Sequences', [$garden('sequences')]),
            $entry('Find the pattern', 'Loops', [$garden('loops')]),
            $entry('Choose your path', 'Conditions', [$garden('conditions')]),
            $entry('Paint with coordinates', 'Coordinates', [$arcade('pixel-studio', ['pixels' => ['1 1 mint', '2 2 peach', '3 3 lavender']])]),
            $entry('A variable changes', 'Variables', [$arcade('number-machine', ['start' => 3, 'target' => 18])]),
            $entry('Read your first code', 'Python expressions', [$quiz('Python: predict the value', [['Python: x = 3\nx = x * 2 + 1\nWhat is x?', '7', '9', 'Multiplication happens before addition: 3 * 2 + 1 is 7.'], ['Python: print(8 // 3)\nWhat is printed?', '2', '2.666...', 'Integer floor division returns the quotient rounded down.']])]),
            $entry('Put data in order', 'Arrays', [$arcade('sort-lab', ['items' => [4, 2, 3, 1]])]),
            $entry('Your first terminal mission', 'CLI', [$terminal(2, 'release.txt')]),
            $entry('Branch detective', 'Python conditions', [$quiz('Follow the branch', [['Python: n = 4\nif n > 5: print("large")\nelse: print("small")\nWhat prints?', 'small', 'large', '4 > 5 is false, so the else branch runs.'], ['Python: True and False evaluates to?', 'False', 'True', 'Both operands must be true for and to be true.']])]),
            $entry('Boss: the logic gate', 'Sequences, loops and conditions', [$conditions, $quiz('Logic gate checkpoint', $logic)]),
            $entry('Build a pixel pattern', 'Indexed data', [$arcade('pixel-studio', ['pixels' => ['1 1 mint', '1 2 peach', '1 3 lavender', '2 2 mint', '3 1 lavender', '3 3 peach']])]),
            $entry('Trace a Java loop', 'Java loops', [$quiz('Java: count the iterations', [['Java: int sum = 0;\nfor (int i = 1; i <= 3; i++) sum += i;\nWhat is sum?', '6', '3', 'The loop adds 1, then 2, then 3.'], ['Java: int x = 7 / 2;\nWhat is x?', '3', '3.5', 'Dividing integer operands uses integer division.']])]),
            $entry('Sort five signals', 'Sorting', [$arcade('sort-lab', ['items' => [5, 3, 1, 4, 2]])]),
            $entry('Inspect before copying', 'CLI inspection', [$terminal(4, 'deploy.txt')]),
            $entry('Trace a C++ array', 'C++ indexing', [$quiz('C++: follow the data', [['C++: int a[] = {4, 7, 9};\nWhat is a[1]?', '7', '4', 'Array indexing starts at zero.'], ['C++: int n = 2;\nn *= 3;\nn -= 1;\nWhat is n?', '5', '3', 'Update the value in sequence: 2 * 3 - 1 = 5.'], ['C++: 5 % 2 evaluates to?', '1', '2', 'The remainder after dividing 5 by 2 is 1.']])]),
            $entry('From value to algorithm', 'State transformations', [$arcade('number-machine', ['start' => -4, 'target' => 36]), $quiz('Explain the state', [['Do arithmetic instructions always commute?', 'No: order can change the result', 'Yes: all orders give the same answer', 'Adding before multiplying can differ from multiplying before adding.']])]),
            $entry('A complete color matrix', 'Coordinate systems', [$arcade('pixel-studio', ['pixels' => ['1 1 mint', '1 2 peach', '1 3 lavender', '2 1 peach', '2 2 lavender', '2 3 mint', '3 1 lavender', '3 2 mint', '3 3 peach']])]),
            $entry('Compare three languages', 'Types and expressions', [$quiz('Python, Java and C++', [['Python: len([2, 4, 6]) returns?', '3', '6', 'Length counts elements, not their values.'], ['Java: "a".equals("a") returns?', 'true', 'false', 'equals compares the string contents.'], ['C++: bool ready = (3 < 5);\nWhat is ready?', 'true', 'false', 'A comparison produces a Boolean value.']])]),
            $entry('Six items, one plan', 'Algorithm planning', [$arcade('sort-lab', ['items' => [6, 5, 4, 3, 2, 1]])]),
            $entry('Boss: the data vault', 'CLI, arrays and code tracing', [$terminal(6, 'vault.txt'), $arcade('sort-lab', ['items' => [6, 2, 5, 1, 4, 3]]), $quiz('Vault checkpoint', [['Why inspect a file before copying it?', 'To confirm its contents match the objective', 'Its name guarantees the contents', 'Names alone do not prove which data a file contains.'], ['After sorting [6, 2, 5, 1, 4, 3], which element is first?', '1', '6', 'Ascending order starts with the smallest value.']])]),
            $entry('Spot an off-by-one', 'Loop boundaries', [$quiz('Boundary detective', [['Python: list(range(1, 4)) is?', '[1, 2, 3]', '[1, 2, 3, 4]', 'The stop value is excluded.'], ['Java: for (int i = 0; i < 3; i++)\nHow many iterations?', '3', '4', 'The counter takes the values 0, 1 and 2.'], ['C++: a has 5 elements. What is the last valid index?', '4', '5', 'Zero-based indexing makes the last index length minus one.']])]),
            $entry('Navigate a crowded workspace', 'CLI arguments', [$terminal(8, 'final.txt')]),
            $entry('Functions return a result', 'Functions', [$quiz('Follow the return value', [['Python: def twice(n): return n * 2\nWhat is twice(3) + twice(1)?', '8', '6', 'Each call returns a result: 6 + 2 = 8.'], ['Java: static int next(int n) { return n + 1; }\nWhat is next(next(2))?', '4', '3', 'The inner call returns 3, then the outer call returns 4.'], ['C++: int square(int n) { return n * n; }\nWhat is square(4)?', '16', '8', 'The function multiplies the argument by itself.']])]),
            $entry('Solve and explain', 'Data and conditions', [$arcade('sort-lab', ['items' => [4, 1, 6, 2, 5, 3]]), $quiz('Why the algorithm works', [['Which condition says an array is ascending?', 'Every adjacent left value is no greater than its right value', 'The first value is larger than the last', 'Ascending order must hold for every neighboring pair, not just the endpoints.'], ['When should a conditional collection action run?', 'Only when a crystal is on the current tile', 'After every move regardless of the tile', 'A condition selects actions based on the current state.']])]),
            $entry('The next horizon', 'Programming foundations', [$conditions, $quiz('Foundation synthesis', [['Python: sum([1, 2, 3]) is?', '6', '3', 'sum adds the values in the collection.'], ['Java: int n = 0;\nwhile (n < 3) n++;\nWhat is n afterwards?', '3', '2', 'After incrementing to 3, the condition is false.'], ['C++: int a[] = {2, 4, 6};\nWhat is a[0] + a[2]?', '8', '6', 'The first and third elements are 2 and 6.']])]),
            $entry('Switch on a decision', 'Boolean AND', [$arcade('logic-switchboard', ['inputs' => [false, true, false], 'gate' => 'and', 'target' => true])]),
            $entry('Forge your first label', 'Text pipelines', [$arcade('string-forge', ['start' => '  code  ', 'target' => 'EDOC_2'])]),
            $entry('A stack remembers', 'Last in, first out', [$arcade('memory-trail', ['start' => [1, 4, 3], 'target' => [1, 3, 4, 4]])]),
            $entry('Trace a string slice', 'Python strings', [$quiz('Predict a text transformation', [['Python: "coding"[1:4] returns?', 'odi', 'odin', 'The start index is included and the stop index is excluded.'], ['Python: "  Kody  ".strip().lower() returns?', '"kody"', '"  kody  "', 'strip removes surrounding whitespace, then lower changes the letters.'], ['Python: "ab" * 3 returns?', 'ababab', 'ab3', 'Multiplying a string repeats its contents.']])]),
            $entry('Boss: signal station', 'Circuits, text and stacks', [$arcade('logic-switchboard', ['inputs' => [true, true, false, false], 'gate' => 'xor', 'target' => true]), $arcade('string-forge', ['start' => '  Signal  ', 'target' => 'LANGIS_READY']), $arcade('memory-trail', ['start' => [1, 2, 3, 4], 'target' => [1, 2, 4, 3, 3]])]),
            $entry('Any signal can open the gate', 'Boolean OR', [$arcade('logic-switchboard', ['inputs' => [true, false, true, false], 'gate' => 'or', 'target' => false])]),
            $entry('Normalize a player name', 'Text normalization', [$arcade('string-forge', ['start' => '  PLAYER_7  ', 'target' => 'player_7_ready'])]),
            $entry('Restore the stack', 'Stack invariants', [$arcade('memory-trail', ['start' => [2, 7, 8, 9], 'target' => [2, 7, 4, 4]])]),
            $entry('Read a Java collection', 'Java collections', [$quiz('Follow the collection', [['Java: var names = List.of("Ada", "Lin", "Sam"); What is names.get(1)?', 'Lin', 'Ada', 'Collection indexes begin at zero.'], ['Java: an ArrayList starts empty, then add(4), add(7), remove(0). What remains?', '[7]', '[4]', 'remove(0) removes the first element, not the value zero.'], ['Java: a list has size 3. Which index is invalid?', '3', '2', 'Valid indexes are 0, 1 and 2.']])]),
            $entry('Find the release note', 'CLI investigation', [$terminal(8, 'release-ready.txt')]),
            $entry('An odd signal', 'Boolean parity', [$arcade('logic-switchboard', ['inputs' => [true, false, true, false], 'gate' => 'xor', 'target' => true]), $quiz('Explain parity', [['When four inputs are true, what does XOR produce?', 'false', 'true', 'An even number of true inputs produces false.'], ['Does XOR require every input to be true?', 'No', 'Yes', 'AND requires every input; XOR tests parity.']])]),
            $entry('A mirrored message', 'Transformation order', [$arcade('string-forge', ['start' => '  Launch_3  ', 'target' => '3_hcnual_ok'])]),
            $entry('Compose a stack plan', 'Stack composition', [$arcade('memory-trail', ['start' => [8, 6, 4, 2], 'target' => [8, 6, 2, 4, 4, 1]])]),
            $entry('C++ value detective', 'C++ references', [$quiz('Follow a value and its reference', [['C++: int n = 3; int& r = n; r += 4; What is n?', '7', '3', 'r refers to the same variable, so changing it changes n.'], ['C++: int a[] = {2, 5, 8}; a[1] = a[0] + a[2]; What is a[1]?', '10', '5', 'The assignment replaces the middle element with 2 + 8.'], ['C++: bool ok = !(3 > 5); What is ok?', 'true', 'false', 'The comparison is false; negation makes it true.']])]),
            $entry('Boss: systems workshop', 'Data, CLI and boundary checks', [$terminal(8, 'workshop.txt'), $arcade('memory-trail', ['start' => [9, 1, 5, 3, 7], 'target' => [9, 1, 5, 7, 3, 3]]), $quiz('Workshop checkpoint', [['A stack is full. What should a bounded push operation do?', 'Reject the push without changing the stack', 'Silently remove another value', 'A capacity constraint must preserve existing data.'], ['Why inspect a file before copying?', 'Names do not guarantee its contents', 'It changes the file contents', 'Inspection confirms the data matches the objective.'], ['A zero-based array has six elements. Its last index is?', '5', '6', 'The final index is the length minus one.']])]),
            $entry('Every switch matters', 'Compound conditions', [$arcade('logic-switchboard', ['inputs' => [false, false, false, false], 'gate' => 'and', 'target' => true]), $quiz('Choose a condition', [['Which condition requires both age >= 18 and verified to be true?', 'age >= 18 AND verified', 'age >= 18 OR verified', 'AND requires both conditions.']])]),
            $entry('Prepare your workspace', 'Virtual filesystem', [$terminal(8, 'workspace-ready.txt'), $arcade('string-forge', ['start' => '  DEPLOY  ', 'target' => 'deploy_v2'])]),
            $entry('Data structure detective', 'Stacks and arrays', [$arcade('memory-trail', ['start' => [5, 4, 3, 2, 1], 'target' => [5, 4, 3, 1, 2, 2]]), $quiz('Pick the structure', [['Which structure models an Undo history most directly?', 'A stack of recent actions', 'An unordered set', 'Undo removes the most recently recorded action first.'], ['Does popping an empty stack produce a valid item?', 'No; it must be handled as an invalid operation', 'Yes; it always returns zero', 'There is no stored item to return.']])]),
            $entry('Follow nested functions', 'Reusable functions', [$quiz('Trace nested calls', [['Python: def next(n): return n + 1\nWhat is next(next(next(2)))?', '5', '3', 'Each nested call adds one.'], ['Java: static int twice(int n) { return n * 2; } What is twice(twice(3))?', '12', '9', 'The inner call returns 6 and the outer returns 12.'], ['C++: int f(int n) { return n + 2; } What is f(3) * f(1)?', '15', '8', 'The calls return 5 and 3; multiply those results.']])]),
            $entry('Debug the state', 'Debugging state transitions', [$arcade('number-machine', ['start' => -7, 'target' => 81]), $quiz('Check a state change', [['Python: n = 2\nn += 3\nn *= 4\nWhat is n?', '20', '14', 'Apply each mutation in order: first 5, then 20.'], ['A program reaches the correct intermediate value but changes it afterwards. Is the final objective satisfied?', 'Only if the final state still matches the objective', 'Yes; intermediate values always count', 'Completion checks the final state of the confirmed program.']])]),
            $entry('Explain the decision', 'Testing Boolean rules', [$arcade('logic-switchboard', ['inputs' => [true, true, true, true], 'gate' => 'xor', 'target' => true]), $quiz('Boundary detective', [['Which test checks the boundary for n >= 10?', 'n = 9 and n = 10', 'Only n = 100', 'Adjacent values on either side show where the rule changes.'], ['What is false OR true?', 'true', 'false', 'OR is true when at least one operand is true.']])]),
            $entry('Build a release label', 'Text and filenames', [$arcade('string-forge', ['start' => '  Release_50  ', 'target' => '05_ESAELER_final']), $terminal(8, 'release-50.txt')]),
            $entry('Protect a stack boundary', 'Bounded data structures', [$arcade('memory-trail', ['start' => [9, 8, 7, 6, 5, 4], 'target' => [9, 8, 7, 6, 4, 5]]), $quiz('Reason about capacity', [['A stack has six values and capacity six. Which operation can run safely?', 'swap the last two values', 'push one more value', 'Swapping keeps the size unchanged.']])]),
            $entry('Prepare for the summit', 'Algorithm planning', [$arcade('sort-lab', ['items' => [6, 3, 5, 2, 4, 1]]), $quiz('Choose an invariant', [['After an ascending sort, what must hold?', 'Each value is no greater than the next', 'Only the first and last values matter', 'The ordering must hold between every adjacent pair.'], ['Python: sum(range(1, 5)) returns?', '10', '15', 'The values are 1, 2, 3 and 4; the stop is excluded.']])]),
            $entry('Boss: the summit', 'Foundation mastery', [$arcade('logic-switchboard', ['inputs' => [false, true, false, false], 'gate' => 'and', 'target' => true]), $arcade('string-forge', ['start' => '  Summit  ', 'target' => 'TIMMUS_50']), $arcade('memory-trail', ['start' => [1, 3, 5, 7, 9], 'target' => [1, 3, 5, 9, 7, 7]]), $quiz('Summit checkpoint', [['Why validate a result on the server?', 'Browser state can be altered', 'It makes every answer correct', 'Trusted validation replays the submitted operations against the saved scenario.'], ['Which structure removes its latest item first?', 'A stack', 'An unordered set', 'Stacks use last in, first out.'], ['When do two transformations commute?', 'When either order produces the same result', 'Whenever both use the same variable', 'Commutativity is a property of the operations, not the variable name.']])]),
        ];
    }
}
