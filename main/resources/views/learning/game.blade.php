@pushOnce('page-scripts', 'play')
    @vite('resources/js/play.js')
@endPushOnce
<section class="game-card garden-game" data-coding-game="{{ json_encode($game, JSON_THROW_ON_ERROR) }}" @if(isset($completionUrl) || isset($module)) data-completion-url="{{ $completionUrl ?? route('play.game', $module) }}" @endif tabindex="0" aria-label="{{ $game['title'] }} coding game. When this game is focused, arrow keys add moves; Enter runs your program.">
    <div class="game-heading"><div><span class="game-kicker">{{ $game['concept'] }}</span><h2>{{ $game['title'] }}</h2></div></div>
    @php($briefing = app(\App\Services\Games\LessonBriefings::class)->for($game))
    <div class="garden-workbench">
    <div class="game-world">
        <div class="scene-heading"><h3>Game state</h3><span data-execution-label>Not run</span></div>
        <div class="world-label"><span class="world-mission"><span aria-hidden="true"><x-action-icon name="flag"/></span> Reach the flag</span><span data-game-objectives>{{ count($game['crystals']) }} crystals left</span></div>
        <div class="game-board" data-game-board role="img" aria-label="Garden with {{ $game['width'] }} columns and {{ $game['height'] }} rows. Start: column {{ $game['start'][0] + 1 }}, row {{ $game['start'][1] + 1 }}. Goal: column {{ $game['goal'][0] + 1 }}, row {{ $game['goal'][1] + 1 }}. Path coordinates: {{ implode('; ', array_map(fn($p) => ($p[0] + 1).','.($p[1] + 1), $game['path'])) }}. Crystal coordinates: {{ implode('; ', array_map(fn($p) => ($p[0] + 1).','.($p[1] + 1), $game['crystals'])) ?: 'none' }}."></div>
        <details class="board-legend-help"><summary>Board guide</summary><div class="board-legend"><span><i class="legend-kody" aria-hidden="true"></i>Your explorer</span><span><i class="legend-path" aria-hidden="true"></i>Follow the path</span><span><i class="legend-goal" aria-hidden="true"></i>Goal</span></div></details>
    </div>
    <div class="game-controls" tabindex="0" role="region" aria-label="Instructions and program">
        @include('learning.briefing')
        <div class="program-heading"><h3>Pseudocode</h3><span data-game-progress>0 / {{ $game['maxCommands'] }} instructions</span></div>
        <div class="command-buttons" aria-label="Add movement instructions"><button type="button" data-command="up" aria-label="Add up">Up</button><button type="button" data-command="left" aria-label="Add left">Left</button><button type="button" data-command="down" aria-label="Add down">Down</button><button type="button" data-command="right" aria-label="Add right">Right</button><button class="undo-command" type="button" data-game-undo aria-label="Remove last instruction" title="Undo">Undo</button></div>
        @if($game['mode'] === 'loop' || ($designerShell ?? false))<label class="game-option"><input type="checkbox" data-game-repeat> Repeat my instructions 2 times</label>@endif
        @if($game['mode'] === 'conditional' || ($designerShell ?? false))<label class="game-option"><input type="checkbox" data-game-conditional> If there’s a crystal, collect it after moving</label>@endif
        <div class="program-track trace-source" data-game-program data-readable-program role="group" aria-label="Readable program" tabindex="0"><span>Add a move to start your program</span></div>
        <div class="program-edit-tools"><button type="button" class="game-reset" data-step-earlier disabled aria-label="Move selected instruction earlier">Move up</button><button type="button" class="game-reset" data-step-later disabled aria-label="Move selected instruction later">Move down</button><button type="button" class="game-reset" data-step-remove disabled aria-label="Remove selected">Remove</button></div>
        <section data-concept-check class="concept-check" hidden aria-label="Concept check"></section>
        <div class="game-toolbar"><button class="button button-play" type="button" data-game-run aria-label="Run">Run</button><button type="button" class="game-reset" data-game-reset>Reset</button><button type="button" class="game-reset" data-game-hint aria-label="Hint" title="Hint">Hint</button></div>
        <p class="game-feedback" data-game-feedback role="status" aria-live="polite" tabindex="0">Add instructions, then run your program.</p>
        <div class="game-extras"><details class="game-help"><summary>Keyboard & tips</summary><p>Focus the game, then use arrow keys to add moves and Enter to run. Select an instruction to move or remove it. With an instruction focused, Alt + Left/Right reorders it and Delete removes it.</p></details></div>
        <div class="game-success" data-game-success hidden><p><b>Goal reached. <x-action-icon name="spark"/></b><span>{{ $game['learningIdea'] }}</span></p>@if(isset($module))<a href="{{ route('dashboard') }}" class="quiet-link">Continue playing <x-action-icon name="right"/></a>@elseif($trial ?? false)@auth<a href="{{ route('dashboard') }}" class="quiet-link">Continue your saved trail <x-action-icon name="right"/></a>@else<p>This trial stays here. Create an account to start saving your wins.</p><a href="{{ route('register') }}" class="quiet-link">Create an account <x-action-icon name="right"/></a><a href="{{ route('login') }}" class="quiet-link">Already joined? Sign in <x-action-icon name="right"/></a>@endauth
@else<a href="{{ route('learning.catalog') }}" class="quiet-link">Find another module <x-action-icon name="right"/></a>@endif</div>
        <noscript><p class="game-feedback">Turn on JavaScript to play. You can still browse modules and create an account.</p></noscript>
    </div>
    </div>
</section>
