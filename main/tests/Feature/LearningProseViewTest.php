<?php

use App\Models\CodingChallengeRevision;
use App\Models\ModuleRevision;

test('module lesson renders formatted prose without activating embedded markup', function () {
    $revision = new ModuleRevision([
        'title' => 'Loops',
        'description' => 'Practice repeating a step.',
        'content' => "## Trace the loop\n\n<p onclick=alert(1)>untrusted markup</p>",
    ]);

    $html = view('content.lesson', ['revision' => $revision, 'preview' => true])->render();

    expect($html)->toContain('<h2>Trace the loop</h2>', '&lt;p onclick=alert(1)&gt;')
        ->not->toContain('<p onclick=');
});

test('challenge partial formats the problem and keeps input and output as prose', function () {
    $revision = new CodingChallengeRevision([
        'language' => 'python',
        'description' => 'Compare **two values**.',
        'rules' => "- Keep both values.\n- Report the larger one.",
        'input_format' => "First value\nSecond value",
        'output_format' => '[output](javascript:alert%281%29)',
    ]);

    $html = view('challenges.problem', ['revision' => $revision])->render();

    expect($html)->toContain('<strong>two values</strong>', '<li>Keep both values.</li>', "First value<br>\nSecond value", 'output')
        ->not->toContain('href=', '<script');
});
