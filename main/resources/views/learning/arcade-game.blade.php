<section class="game-card arcade-game" data-arcade-game="{{ json_encode($game, JSON_THROW_ON_ERROR) }}" @if(isset($completionUrl)) data-completion-url="{{ $completionUrl }}" @endif aria-label="{{ $game['title'] }}">
    <div class="game-heading"><div><span class="game-kicker">BUILD SOMETHING WITH CODE</span><h2>{{ $game['title'] }}</h2></div><span class="game-badge">{{ isset($completionUrl) ? 'LEARNING' : 'PRACTICE' }}</span></div>
    <div class="game-controls"><span class="step-pill">{{ $game['concept'] }}</span><p>{{ $game['instructions'] }}</p>
        <div data-arcade-world class="arcade-world" aria-label="Activity state"></div>
        <div data-arcade-controls class="arcade-controls"></div>
        <label class="arcade-code-label">Your program · up to 12 instructions<textarea data-arcade-code rows="5" maxlength="1212" spellcheck="false" autocomplete="off" aria-label="Your program"></textarea></label>
        <p class="field-hint" data-arcade-help></p>
        <div class="game-toolbar"><button class="button button-play" type="button" data-arcade-run>Run program ▶</button><button class="game-reset" type="button" data-arcade-reset>Reset</button><button class="game-reset" type="button" data-arcade-hint>Hint</button></div>
        <pre data-arcade-output class="arcade-output" role="status" aria-live="polite">Ready when you are.</pre>
        <noscript><p>Enable JavaScript to play this activity.</p></noscript>
    </div>
</section>
