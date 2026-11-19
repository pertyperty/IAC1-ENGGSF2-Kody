<?php

use App\Services\Games\AssessmentChallenges;
use App\Services\Games\GameAssessment;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$games = app(AssessmentChallenges::class)->catalog();
$cases = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$engine = app(GameAssessment::class);
echo json_encode(['games' => $games, 'results' => array_map(fn ($case) => $engine->succeeds($games[$case['id']], $case['input']), $cases)], JSON_THROW_ON_ERROR);
