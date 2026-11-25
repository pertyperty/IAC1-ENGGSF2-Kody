<section id="solution" data-quest-participation aria-labelledby="solution-heading">
    <h2 id="solution-heading">Your solution</h2>
    <p>{{ 3 - ($participation->attempts ?? 0) }} of 3 attempts remain for {{ $attemptScope }}.</p>
    @if($errors->any())
        <div class="lesson-note" role="alert">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    @if($participation?->active_submission_id)
        <p class="lesson-note">Your last attempt is still being evaluated.</p>
        <a class="button button-play" href="{{ route('challenge-attempts.show', $participation->active_submission_id) }}">View active attempt</a>
    @elseif(($participation->attempts ?? 0) >= 3)
        <p class="lesson-note">You've used your three attempts. Explore another challenge to keep practicing.</p>
    @elseif($ready)
        <form class="studio-form challenge-solution-form" method="POST" action="{{ $attemptAction }}">
            @csrf
            <input type="hidden" name="revision_id" value="{{ $revision->id }}">
            <input type="hidden" name="language" value="{{ $revision->language }}">
            <input type="hidden" name="confirmation_id" value="{{ $confirmationId }}">
            @php $sourceInput = old('source_code', $retrySource ?? $revision->starter_code); @endphp
            <div class="challenge-editor-header">
                <label for="source-code">Your {{ config('challenges.languages')[$revision->language] }} solution</label>
                @if(filled($revision->starter_code ?? null))
                    <button type="button" class="button button-secondary button-small" data-editor-reset-starter hidden>Reset to starter code</button>
                @endif
            </div>
            <div class="challenge-editor" data-challenge-editor @if(filled($revision->starter_code ?? null)) data-starter-code="{{ $revision->starter_code }}" @endif>
                <div class="challenge-editor-gutter" data-editor-gutter aria-hidden="true">1</div>
                <textarea id="source-code" class="challenge-source" name="source_code" rows="16" spellcheck="false" required aria-describedby="attempt-help">{{ "\n".(is_string($sourceInput) ? $sourceInput : '') }}</textarea>
            </div>
            <p class="field-hint challenge-editor-meta">
                <span data-editor-bytes aria-live="polite">0 / 65,536 bytes</span> ·
                <span>Press Tab to indent, Shift+Tab to outdent, or Escape then Tab to move focus.</span>
            </p>
            @if($retrySource !== null && ! session()->hasOldInput('source_code'))
                <p class="field-hint">Your previous solution is loaded. Edit it before submitting again.</p>
            @endif
            <p id="attempt-help">Submitting uses one attempt. You can leave while we check your code.</p>
            <label class="checkbox-row"><input type="checkbox" name="confirmed" value="1" required> Use one attempt for this solution.</label>
            <button class="button button-play" type="submit">Submit solution</button>
        </form>
    @else
        <div class="execution-pending" data-execution-pending>
            <h3><x-action-icon name="code"/>@can('create', \App\Models\ChallengeSubmission::class) Live code execution: setup pending @else Participant access required @endcan</h3>
            <p>You can still read the problem and examples. No attempt will be used.</p>
            <button class="button button-secondary" type="button" disabled>Submission unavailable</button>
            <p><a class="button button-play" href="{{ route('arcade') }}"><x-action-icon name="game"/> Visit Practice</a></p>
        </div>
    @endif
    @if($attempts->isNotEmpty())
        <h2>Your attempts</h2>
        <ul>
            @foreach($attempts as $attempt)
                <li><a href="{{ route('challenge-attempts.show', $attempt->id) }}">Attempt {{ $attempt->attempt }} · {{ $attempt->status }}</a></li>
            @endforeach
        </ul>
    @endif
</section>
