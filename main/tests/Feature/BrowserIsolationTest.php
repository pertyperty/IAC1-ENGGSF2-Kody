<?php

use Symfony\Component\Process\Process;

test('browser CLI fixtures reject nonisolated nonlocal and non-testing databases before connecting', function (array $overrides) {
    $environment = array_replace(['APP_ENV' => 'testing', 'KODY_BROWSER_ISOLATED' => '1',
        'DB_DATABASE' => 'kody_browser_'.str_repeat('a', 32), 'DB_HOST' => '127.0.0.1'], $overrides);
    $process = new Process([PHP_BINARY, base_path('tests/Browser/support/fixture.php'), 'drop'], base_path(), $environment);
    $process->setTimeout(10)->run();
    expect($process->getExitCode())->toBe(1)->and($process->getOutput())->toBe('')
        ->and($process->getErrorOutput())->toBe("Isolated browser fixture failed (RuntimeException).\n");
})->with([
    [['APP_ENV' => 'production']], [['KODY_BROWSER_ISOLATED' => '0']], [['DB_HOST' => 'db.example.test']],
    [['DB_DATABASE' => 'kody_test']], [['DB_DATABASE' => 'kody_browser_invalid']],
]);
