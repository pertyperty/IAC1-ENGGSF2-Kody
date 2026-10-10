<section class="journey-next {{ $trail['finished'] ? 'journey-finished' : '' }}" data-course-journey="{{ $course->id }}" aria-label="Saved course progress">
    <div><p class="overline">YOUR SAVED TRAIL</p><h2 data-journey-title>{{ $trail['finished'] ? 'Journey cleared!' : ($trail['current_completed'] && $trail['next'] ? 'Ready for your next adventure.' : 'One adventure at a time.') }}</h2>
        <p data-journey-count>{{ $trail['completed'] }} of {{ $trail['total'] }} adventures completed.</p>
        <progress data-journey-progress aria-label="Saved journey completion" value="{{ $trail['completed'] }}" max="{{ max(1, $trail['total']) }}"></progress>
        <p class="field-hint" data-journey-note role="status">{{ $trail['finished'] ? 'All your lesson progress is saved. Discover another journey or revisit a favorite.' : ($trail['next'] ? 'Keep going when you’re ready. Your next step is available.' : ($trail['current_completed'] ? 'Open the trail to check the remaining adventures.' : 'Complete this adventure to save your course progress.')) }}</p>
    </div>
    <div class="journey-actions">
        <a data-journey-next class="button button-play" href="{{ $trail['next']['url'] ?? route('course-learning.show', $course) }}" @if(!$trail['next']) hidden @endif>Next adventure →</a>
        <a class="button button-secondary" href="{{ $trail['finished'] ? route('course-learning.catalog') : route('course-learning.show', $course) }}" data-journey-overview>{{ $trail['finished'] ? 'Find another journey' : 'View your trail' }}</a>
    </div>
</section>
