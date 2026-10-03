<?php

use App\Services\Games\CommandGarden;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$instances = (require dirname(__DIR__, 2).'/config/learning.php')['instances'];
$cases = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$engine = new CommandGarden;
echo json_encode(array_map(fn (array $case) => $engine->succeeds($instances[$case['level']], $case['program'], $case['repeat'], $case['conditional']), $cases), JSON_THROW_ON_ERROR);
