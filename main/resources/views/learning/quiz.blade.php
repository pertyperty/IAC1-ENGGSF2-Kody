<section class="practice-quiz" data-practice-quiz="{{ json_encode($quiz, JSON_THROW_ON_ERROR) }}" @if(isset($completionUrl) || isset($module)) data-completion-url="{{ $completionUrl ?? route('play.quiz', $module) }}" @endif aria-label="Practice quiz">
    <div class="game-heading"><div><p class="overline"><x-action-icon name="game"/> QUIZ GAME · RECALL AND CONNECT</p><h2>{{ $quiz['title'] }}</h2></div><span class="game-badge">{{ isset($completionUrl) || isset($module) ? 'VALIDATED PLAY' : 'LOCAL PRACTICE' }}</span></div>
    @include('learning.briefing', ['game' => $quiz])
    <form>@foreach(app(\App\Services\Games\QuizAuthoring::class)->questions($quiz) as $question)<fieldset data-quiz-question="{{ $question['id'] }}"><legend>{{ $loop->count > 1 ? $loop->iteration.'. ' : '' }}{{ $question['question'] }}</legend>
    @if(($question['type'] ?? 'choice') === 'blank')
        <label>Your short answer<input data-blank-answer type="text" maxlength="100" autocomplete="off"></label><p class="field-hint">{{ $question['case_sensitive'] ? 'Letter case matters. Extra spaces are ignored.' : 'Letter case and extra spaces are ignored.' }}</p>
    @elseif(($question['type'] ?? 'choice') === 'matching')
        @foreach($question['pairs'] as $pair)<label class="quiz-match-row">{{ $pair['left'] }}<select aria-label="{{ $pair['left'] }}" data-match-answer="{{ $pair['id'] }}"><option value="">Choose a match</option>@foreach([...array_slice($question['pairs'], 1), $question['pairs'][0]] as $choice)<option value="{{ $choice['id'] }}">{{ $choice['right'] }}</option>@endforeach</select></label>@endforeach
    @else
        @foreach($question['options'] as $option)<label><input type="radio" name="practice_answer_{{ $question['id'] }}" value="{{ $option['id'] }}">{{ $option['label'] }}</label>@endforeach
    @endif
    <p data-question-feedback role="status"></p></fieldset>@endforeach<button type="submit" class="button button-play"><x-action-icon name="check"/> Check my idea</button></form>
    <p data-quiz-feedback role="status" aria-live="polite">{{ ($towerActivity ?? false) ? 'Kody validates your answers before saving this stage. Complete the final stage for streak credit; tower clears grant no additional XP or KodeBits.' : (isset($completionUrl) || isset($module) ? 'Correct answers are validated by Kody before progress or eligible first-completion XP is saved. This quiz awards no KodeBits.' : 'Try your idea. Preview answers stay here and do not save progress.') }}</p>
    <noscript><p>JavaScript is required to check your practice answer.</p></noscript>
</section>
