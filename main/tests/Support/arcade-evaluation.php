<?php

use App\Services\Games\ArcadeGames;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$engine = new ArcadeGames;
$cases = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
echo json_encode(array_map(fn ($case) => $engine->succeeds(config('arcade')[$case['template']], $case['program']), $cases), JSON_THROW_ON_ERROR);
