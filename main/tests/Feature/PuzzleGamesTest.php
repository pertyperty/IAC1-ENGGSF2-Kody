<?php

use App\Services\Games\ArcadeGames;
use App\Services\Games\TowerAuthoring;
use Illuminate\Validation\ValidationException;

test('new puzzles replay typed instructions and refuse forged wins', function (string $template, array $solution, array $invalid) {
    $games = app(ArcadeGames::class);
    $game = $games->instance($template);
    expect($games->succeeds($game, $solution))->toBeTrue()->and($games->succeeds($game, $invalid))->toBeFalse()
        ->and($games->succeeds($game, array_fill(0, 13, $solution[0])))->toBeFalse();
    expect(app(TowerAuthoring::class)->stages([$game])[0])->toBe($game);
})->with([
    ['logic-switchboard', ['flip 1', 'flip 2'], ['flip 4']],
    ['string-forge', ['upper', 'reverse'], ['eval php']],
    ['memory-trail', ['push 2', 'push 3'], ['pop', 'pop']],
]);

test('new puzzle scenarios reject untyped values impossible targets and extra executable keys', function (string $template, array $scenario) {
    expect(fn () => app(ArcadeGames::class)->instance($template, json_encode($scenario)))
        ->toThrow(ValidationException::class);
})->with([
    ['logic-switchboard', ['inputs' => [1, 0], 'gate' => 'and', 'target' => true]],
    ['logic-switchboard', ['inputs' => [false, false], 'gate' => 'eval', 'target' => true]],
    ['string-forge', ['start' => 'cat', 'target' => 'dog']],
    ['string-forge', ['start' => 'cat', 'target' => 'cat dog']],
    ['memory-trail', ['start' => ['1'], 'target' => [2]]],
    ['memory-trail', ['start' => [1], 'target' => [2], 'script' => 'evil']],
]);
