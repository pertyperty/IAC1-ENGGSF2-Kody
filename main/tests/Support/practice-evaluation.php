<?php

use App\Services\Games\GameAssessment;
use App\Services\Games\PracticeChallenges;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$cases = [];
foreach (config('practice-challenges') as $template => $definition) {
    foreach (array_keys(PracticeChallenges::LEVELS) as $level) {
        $game = app(PracticeChallenges::class)->instance($template, $level);
        $reference = $level === 'challenge' ? config('assessment-challenges.'.$definition['challenge'].'.reference') : $definition[$level]['reference'];
        $input = $reference + ['assessment_answer' => $game['assessment']['check']['answer']];
        $cases[] = compact('template', 'level', 'game', 'input') + ['accepted' => app(GameAssessment::class)->succeeds($game, $input)];
    }
}
echo json_encode($cases, JSON_THROW_ON_ERROR);
