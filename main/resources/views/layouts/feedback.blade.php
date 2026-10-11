<section class="toast-region" data-toast-region aria-label="Feedback notifications">
    @if(session('status'))
    <div class="toast toast-info" data-flash-toast role="status"><span>{{ session('status') }}</span><button type="button" data-toast-dismiss aria-label="Dismiss notification" hidden>×</button></div>
    @endif
    @if($errors->any())
    <div class="toast toast-error" data-flash-toast role="alert"><span>Please check the form. {{ $errors->first() }}</span><button type="button" data-toast-dismiss aria-label="Dismiss notification" hidden>×</button></div>
    @endif
</section>
<dialog class="game-result" data-game-result aria-labelledby="game-result-title" aria-describedby="game-result-message">
    <span class="result-emblem" aria-hidden="true">✦</span><p class="overline" data-result-saved></p>
    <h2 id="game-result-title" data-result-title>Your result</h2><p id="game-result-message" data-result-message></p>
    <div data-result-reactions hidden></div>
    <div class="result-actions"><a class="button button-play" data-result-next hidden>Next lesson →</a><button type="button" class="button button-secondary" data-result-return>↶ Return to lesson</button></div>
</dialog>
