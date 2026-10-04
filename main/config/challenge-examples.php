<?php

// Original Kody problems; examples and hidden tests are shown only to authorized authors.
return [
    'sum-two' => ['title' => 'Combine two energy packs', 'description' => "A robot has two energy packs. Read their integer values and print their total.\n\nExample: input 3 4 produces 7. Think of addition as combining both values; remember that negative values can reduce the total.",
        'rules' => 'Each integer is between -1000 and 1000. Print only the sum and a newline.',
        'input_format' => 'One line with two space-separated integers.', 'output_format' => 'One integer: the sum of the two inputs.',
        'test_cases' => [['input' => "3 4\n", 'expected_output' => "7\n", 'hidden' => false], ['input' => "0 0\n", 'expected_output' => "0\n", 'hidden' => false], ['input' => "-8 3\n", 'expected_output' => "-5\n", 'hidden' => true], ['input' => "1000 1000\n", 'expected_output' => "2000\n", 'hidden' => true], ['input' => "-1000 1000\n", 'expected_output' => "0\n", 'hidden' => true]]],
    'even-or-odd' => ['title' => 'Choose the correct gate', 'description' => "A robot enters the EVEN gate for an even integer and the ODD gate otherwise. Read one integer and print the correct gate name.\n\nExample: 8 produces EVEN, while 7 produces ODD. Hint: an even integer has remainder 0 when divided by 2, including zero and negative even numbers.",
        'rules' => 'The integer is between -1000 and 1000. Output must use uppercase letters.', 'input_format' => 'One integer on one line.', 'output_format' => 'EVEN or ODD followed by a newline.',
        'test_cases' => [['input' => "8\n", 'expected_output' => "EVEN\n", 'hidden' => false], ['input' => "7\n", 'expected_output' => "ODD\n", 'hidden' => false], ['input' => "0\n", 'expected_output' => "EVEN\n", 'hidden' => true], ['input' => "-9\n", 'expected_output' => "ODD\n", 'hidden' => true], ['input' => "-1000\n", 'expected_output' => "EVEN\n", 'hidden' => true]]],
    'countdown' => ['title' => 'Count down to launch', 'description' => "Read a starting number and count down to 1, printing each number on its own line. Print GO! after the countdown.\n\nIf the starting number is zero, print GO! immediately. Hint: update the counter after each printed number and stop before it becomes negative.",
        'rules' => 'The starting integer is between 0 and 10. Do not print extra spaces or blank lines.', 'input_format' => 'One integer on one line.', 'output_format' => 'Numbers from the input down to 1, one per line, then GO! and a newline.',
        'test_cases' => [['input' => "3\n", 'expected_output' => "3\n2\n1\nGO!\n", 'hidden' => false], ['input' => "0\n", 'expected_output' => "GO!\n", 'hidden' => false], ['input' => "1\n", 'expected_output' => "1\nGO!\n", 'hidden' => true], ['input' => "10\n", 'expected_output' => "10\n9\n8\n7\n6\n5\n4\n3\n2\n1\nGO!\n", 'hidden' => true]]],
];
