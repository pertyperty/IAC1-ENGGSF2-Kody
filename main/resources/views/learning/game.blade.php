<section class="game-card" data-coding-game="{{ json_encode($game, JSON_THROW_ON_ERROR) }}" @if(isset($completionUrl) || isset($module)) data-completion-url="{{ $completionUrl ?? route('play.game', $module) }}" @endif aria-label="{{ $game['title'] }} coding game">
    <div class="game-heading"><div><span class="game-kicker">A LITTLE CODING ADVENTURE</span><h2>{{ $game['title'] }} <span aria-hidden="true">✿︎</span></h2></div><span class="game-badge">{{ ($trial ?? false) ? 'FREE TO TRY' : 'PRACTICE' }}</span></div>
    <div class="game-world"><div class="world-label"><span>Guide Kody to the flag</span><span data-game-progress>Ready to explore</span></div><div class="game-board" data-game-board role="img" aria-label="Garden path. Kody starts at the left. The flag is on the right."></div></div>
    <div class="game-controls"><div class="game-instructions"><span class="step-pill">{{ $game['concept'] }}</span><p>{{ $game['instructions'] }}</p></div>
        <div class="command-buttons" aria-label="Add movement instructions"><button type="button" data-command="up" aria-label="Add up">↑</button><button type="button" data-command="left" aria-label="Add left">←</button><button type="button" data-command="down" aria-label="Add down">↓</button><button type="button" data-command="right" aria-label="Add right">→</button><button class="undo-command" type="button" data-game-undo aria-label="Remove last instruction">Undo</button></div>
        @if($game['mode'] === 'loop')<label class="game-option"><input type="checkbox" data-game-repeat> Repeat my instructions 2 times</label>@endif
        @if($game['mode'] === 'conditional')<label class="game-option"><input type="checkbox" data-game-conditional> If there’s a crystal, collect it after moving</label>@endif
        <div class="program-track" data-game-program aria-label="Your program"><span>Add an arrow to start your program</span></div>
        <div class="game-toolbar"><button class="button button-play" type="button" data-game-run>Run my code <span aria-hidden="true">▶︎</span></button><button type="button" class="game-reset" data-game-reset>Start over</button><button type="button" class="game-reset" data-game-hint>Hint</button></div>
        <p class="game-feedback" data-game-feedback role="status" aria-live="polite">Small steps count. You can try as many times as you like.</p>
        <div class="game-success" data-game-success hidden><p><b>You made that happen! ✦</b><span>{{ $game['learningIdea'] }}</span></p><a href="{{ route('learning.catalog') }}" class="quiet-link">Explore your next adventure →</a></div>
        <noscript><p class="game-feedback">Turn on JavaScript to play. You can still browse modules and create an account.</p></noscript>
    </div>
</section>
