<h2>Make your move</h2><p>{{ 3 - ($participation->attempts ?? 0) }} of 3 attempts remain for {{ $attemptScope }}.</p>
@if($errors->any())<div class="lesson-note" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($participation?->active_submission_id)<p class="lesson-note">Your last attempt is still being evaluated.</p><a class="button button-play" href="{{ route('challenge-attempts.show', $participation->active_submission_id) }}">View active attempt →</a>
@elseif(($participation->attempts ?? 0) >= 3)<p class="lesson-note">You've used your three attempts. Explore another quest to keep practicing.</p>
@elseif($ready)<form class="studio-form" method="POST" action="{{ $attemptAction }}">@csrf
<input type="hidden" name="revision_id" value="{{ $revision->id }}"><input type="hidden" name="language" value="{{ $revision->language }}"><input type="hidden" name="confirmation_id" value="{{ $confirmationId }}">
<label for="source-code">Your {{ config('challenges.languages')[$revision->language] }} solution</label><textarea id="source-code" class="challenge-source" name="source_code" rows="16" spellcheck="false" required aria-describedby="attempt-help"></textarea>
<p id="attempt-help">Free participation. Up to 64 KiB of code. Confirming uses one attempt; evaluation continues if you leave this page.</p>
<label class="checkbox-row"><input type="checkbox" name="confirmed" value="1" required> I confirm this solution for evaluation.</label><button class="button button-play" type="submit">Submit my solution →</button></form>
@else<p class="lesson-note">Explore the problem and plan your approach. Code evaluation is not available yet. No attempt will be used.</p><a class="button button-play" href="{{ route('dashboard') }}">Keep playing while you wait →</a>@endif
@if($attempts->isNotEmpty())<h2>Your attempts</h2><ul>@foreach($attempts as $attempt)<li><a href="{{ route('challenge-attempts.show', $attempt->id) }}">Attempt {{ $attempt->attempt }} · {{ $attempt->status }}</a></li>@endforeach</ul>@endif
