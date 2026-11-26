<section id="solution" data-quest-participation aria-labelledby="solution-heading">
    <div class="challenge-pane-heading">
        <h2 id="solution-heading">Your solution</h2>
        <p class="challenge-attempt-budget"><strong>{{ 3 - ($participation->attempts ?? 0) }} / 3</strong> attempts left<span class="sr-only"> for {{ $attemptScope }}</span></p>
    </div>
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
        <form class="studio-form challenge-solution-form" data-challenge-solution method="POST" action="{{ $attemptAction }}">
            @csrf
            <input type="hidden" name="revision_id" value="{{ $revision->id }}">
            <input type="hidden" name="language" value="{{ $revision->language }}">
            <input type="hidden" name="confirmation_id" value="{{ $confirmationId }}">
            @php
                $hint = ($revision->language === 'python' ? '# ' : '// ').'Read the input from stdin. Print only the required output (no prompts or debug text).';
                $starter = $revision->starter_code ?? match ($revision->language) {
                    'java' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        $hint\n    }\n}\n",
                    'cpp' => "#include <iostream>\n\nint main() {\n    $hint\n    return 0;\n}\n",
                    default => "$hint\n",
                };
                $sourceInput = old('source_code', $retrySource ?? $starter);
            @endphp
            <div class="challenge-editor-header">
                <label for="source-code">{{ config('challenges.languages')[$revision->language] }} editor</label>
                <div class="challenge-editor-actions">
                    <button type="button" class="button button-secondary button-small" data-editor-insert-hint hidden>Add hint comment</button>
                    <button type="button" class="button button-secondary button-small" data-editor-reset-starter hidden>Reset to starter code</button>
                    <button type="button" class="button button-secondary button-small" data-editor-undo-reset hidden>Undo reset</button>
                </div>
            </div>
            <div class="challenge-editor" data-challenge-editor data-starter-code="{{ $starter }}" data-hint-comment="{{ $hint }}">
                <div class="challenge-editor-gutter" data-editor-gutter aria-hidden="true">1</div>
                <textarea id="source-code" class="challenge-source" name="source_code" rows="20" spellcheck="false" autocapitalize="off" autocomplete="off" required aria-describedby="attempt-help">{{ "\n".(is_string($sourceInput) ? $sourceInput : '') }}</textarea>
            </div>
            <p class="field-hint challenge-editor-meta">
                <span data-editor-bytes>0 / 65,536 bytes</span> ·
                <span>Press Tab to indent, Shift+Tab to outdent, or Escape then Tab to move focus.</span>
            </p>
            @if($retrySource !== null && ! session()->hasOldInput('source_code'))
                <p class="field-hint">Your previous solution is loaded. Edit it before submitting again.</p>
            @endif
            <div class="challenge-submit-bar">
                <div>
                    <label class="checkbox-row"><input type="checkbox" name="confirmed" value="1" required> Use one attempt for this solution.</label>
                    <p class="field-hint" id="attempt-help">You can leave while we check your code.</p>
                </div>
                <button class="button button-play" type="submit">Submit solution</button>
            </div>
        </form>
    @else
        <div class="execution-pending" data-execution-pending>
            <h3><x-action-icon name="code"/>@can('create', \App\Models\ChallengeSubmission::class) Live code execution: setup pending @else Participant access required @endcan</h3>
            <p>You can still read the problem and examples. No attempt will be used.</p>
            <a class="button button-secondary" href="{{ route('arcade') }}"><x-action-icon name="game"/> Practice in the Arcade</a>
        </div>
    @endif
    @if($attempts->isNotEmpty())
        <section class="challenge-history" id="challenge-history" aria-labelledby="challenge-history-heading">
            <h2 id="challenge-history-heading">Your attempts</h2>
            <ul class="challenge-attempt-list">
                @foreach($attempts as $attempt)
                    <li>
                        <a href="{{ route('challenge-attempts.show', $attempt->id) }}" aria-label="Attempt {{ $attempt->attempt }}: {{ $attempt->status }}">
                            <strong>Attempt {{ $attempt->attempt }}</strong>
                            <span class="challenge-verdict" data-verdict="{{ strtolower($attempt->status) }}">{{ $attempt->status }}</span>
                            <x-action-icon name="right"/>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</section>
