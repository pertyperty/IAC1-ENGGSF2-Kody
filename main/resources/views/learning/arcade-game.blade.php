@pushOnce('page-scripts', 'play')
    @vite('resources/js/play.js')
@endPushOnce
<section class="game-card arcade-game" data-arcade-game="{{ json_encode($game, JSON_THROW_ON_ERROR) }}" @if(isset($completionUrl)) data-completion-url="{{ $completionUrl }}" @endif aria-label="{{ $game['title'] }}">
    <div class="game-heading"><div><span class="game-kicker">{{ $game['concept'] }}</span><h2>{{ $game['title'] }}</h2></div></div>
    @include('learning.briefing')
    <div class="arcade-workbench">
        <div class="arcade-scene"><div data-arcade-world class="arcade-world" role="group" aria-label="Activity state"></div><p class="scene-caption" data-arcade-preview-status role="status" tabindex="0">Add instructions, then run your program.</p></div>
        <div class="game-controls">
            <div class="program-heading"><b>Your program</b><span data-arcade-count>0 / {{ $game['maxCommands'] }} instructions</span></div>
            <div data-arcade-controls class="arcade-controls"></div>
            <details class="program-arrange"><summary>Edit or remove instructions</summary><ol class="arcade-program-list" data-arcade-program aria-label="Program instructions"></ol><label class="arcade-code-label">Edit as text<textarea data-arcade-code rows="4" maxlength="1212" spellcheck="false" autocomplete="off" aria-label="Your program"></textarea></label></details>
            <details class="command-reference"><summary>Command reference</summary><p class="field-hint" data-arcade-help></p></details>
            <section data-concept-check class="concept-check" hidden aria-label="Concept check"></section>
            <div class="game-toolbar"><button class="button button-play" type="button" data-arcade-run aria-label="Run program">Run program</button><button class="game-reset" type="button" data-arcade-reset>Reset</button><button class="game-reset" type="button" data-arcade-hint>Hint</button></div>
            <pre data-arcade-output class="arcade-output" role="status" aria-live="polite" tabindex="0" aria-label="Program output">Ready to run.</pre>
            <noscript><p>Enable JavaScript to use this activity.</p></noscript>
        </div>
    </div>
</section>
