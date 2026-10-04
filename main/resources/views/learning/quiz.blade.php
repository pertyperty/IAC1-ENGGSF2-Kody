<section class="practice-quiz" data-practice-quiz="{{ json_encode($quiz, JSON_THROW_ON_ERROR) }}" @if(isset($completionUrl) || isset($module)) data-completion-url="{{ $completionUrl ?? route('play.quiz', $module) }}" @endif aria-label="Practice quiz">
    <p class="overline">ONE MORE LITTLE WIN</p><h2>{{ $quiz['title'] }}</h2>
    <form><fieldset><legend>{{ $quiz['question'] }}</legend>@foreach($quiz['options'] as $option)<label><input type="radio" name="practice_answer" value="{{ $option['id'] }}">{{ $option['label'] }}</label>@endforeach</fieldset><button type="submit" class="button button-dark button-small">Check my idea →</button></form>
    <p data-quiz-feedback role="status" aria-live="polite">{{ isset($completionUrl) || isset($module) ? 'A server-validated correct answer keeps your daily streak. No grades, XP or KodeBits.' : 'Try your idea. Preview answers stay here and do not save progress.' }}</p>
    <noscript><p>JavaScript is required to check your practice answer.</p></noscript>
</section>
