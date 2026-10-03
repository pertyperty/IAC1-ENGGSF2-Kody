<section class="practice-quiz" data-practice-quiz="{{ json_encode($quiz, JSON_THROW_ON_ERROR) }}" aria-label="Practice quiz">
    <p class="overline">ONE MORE LITTLE WIN</p><h2>{{ $quiz['title'] }}</h2>
    <form><fieldset><legend>{{ $quiz['question'] }}</legend>@foreach($quiz['options'] as $option)<label><input type="radio" name="practice_answer" value="{{ $option['id'] }}">{{ $option['label'] }}</label>@endforeach</fieldset><button type="submit" class="button button-dark button-small">Check my idea →</button></form>
    <p data-quiz-feedback role="status" aria-live="polite">A practice check, with no grades or rewards. Give it a try.</p>
    <noscript><p>JavaScript is required to check your practice answer.</p></noscript>
</section>
