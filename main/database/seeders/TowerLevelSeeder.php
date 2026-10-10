<?php

namespace Database\Seeders;

use App\Models\TowerLevel;
use App\Models\TowerRevision;
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
                    'source_notes' => 'Original Kody curriculum and scenarios, 2026-10-10. Editorial learning material; not copied course or repository content.']);
                $level->update(['current_revision_id' => $revision->id]);
            }
        });
    }

    public function curriculum(): array
    {
        $garden = fn (string $slug) => config('learning.instances.'.$slug);
        $arcade = fn (string $slug, array $scenario) => array_replace(config('arcade.'.$slug), ['scenario' => $scenario]);
        $quiz = function (string $title, array $questions): array {
            $items = array_map(fn ($item, $index) => ['id' => 'q'.($index + 1), 'question' => str_replace('\\n', "\n", $item[0]),
                'options' => [['id' => 'a', 'label' => $item[1]], ['id' => 'b', 'label' => $item[2]]], 'answer' => 'a', 'explanation' => $item[3]], $questions, array_keys($questions));

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
        ];
    }
}
