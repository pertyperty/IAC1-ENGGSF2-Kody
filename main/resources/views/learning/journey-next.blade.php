<section class="journey-next {{ $trail['finished'] ? 'journey-finished' : '' }}" data-course-journey="{{ $course->id }}" aria-label="Saved course progress">
    <div><p class="overline">COURSE PROGRESS</p><h2 data-journey-title>{{ $trail['finished'] ? 'Course complete' : ($trail['current_completed'] && $trail['next'] ? 'Your next lesson is ready.' : 'Course progress') }}</h2>
        <p data-journey-count>{{ $trail['completed'] }} of {{ $trail['total'] }} lessons completed.</p>
        <progress data-journey-progress aria-label="Saved course completion" value="{{ $trail['completed'] }}" max="{{ max(1, $trail['total']) }}"></progress>
        <p class="field-hint" data-journey-note role="status">{{ $trail['finished'] ? 'Your progress is saved. Review a lesson or find another course.' : ($trail['next'] ? 'Keep going when you’re ready. Your next step is available.' : ($trail['current_completed'] ? 'View the course to check your remaining lessons.' : 'Complete this lesson to save your course progress.')) }}</p>
    </div>
    <div class="journey-actions">
        <a data-journey-next class="button button-play" href="{{ $trail['next']['url'] ?? route('course-learning.show', $course) }}" @if(!$trail['next']) hidden @endif>Next lesson</a>
        <a class="button button-secondary" href="{{ $trail['finished'] ? route('course-learning.catalog') : route('course-learning.show', $course) }}" data-journey-overview>{{ $trail['finished'] ? 'Find another course' : 'View course' }}</a>
    </div>
</section>
