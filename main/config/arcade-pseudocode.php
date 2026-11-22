<?php

// The same small grammar is bundled by Vite for browser-side simulation.
return json_decode(file_get_contents(resource_path('js/games/arcade-pseudocode.json')), true, flags: JSON_THROW_ON_ERROR);
