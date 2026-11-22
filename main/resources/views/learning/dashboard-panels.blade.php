<div class="dashboard-grid">
@if($progress)
    <section class="dashboard-panel" aria-labelledby="dashboard-journeys">
        <div class="section-heading">
            <h2 id="dashboard-journeys">Courses</h2>
            <a class="quiet-link" href="{{ route('course-learning.mine') }}">Library</a>
        </div>
        <ul class="compact-activity">
            @forelse($dashboard['courses']->take(3) as $enrollment)
                <li>
                    @if(in_array($enrollment->course->status, ['Published', 'Archived'], true) && !$enrollment->course->isWithdrawn())
                        <a href="{{ route('course-learning.show', $enrollment->course_id) }}">{{ $enrollment->revision->title }}</a>
                        <span>{{ $enrollment->completed_lessons }} / {{ $enrollment->total_lessons }} modules</span>
                    @else
                        <span>Course unavailable. Progress retained.</span>
                    @endif
                </li>
            @empty
                <li><a href="{{ route('course-learning.catalog') }}">Browse courses</a></li>
            @endforelse
        </ul>
    </section>
    <section class="dashboard-panel" aria-labelledby="dashboard-activity">
        <h2 id="dashboard-activity">Recent modules</h2>
        <ul class="compact-activity">
            @forelse($dashboard['activity']->take(3) as $visit)
                <li>
                    <a href="{{ route('course-learning.lesson', [$visit->course_id, $visit->slot_id]) }}">{{ $visit->title }}</a>
                    <span>{{ $visit->completed_at ? 'Completed' : 'In progress' }}</span>
                </li>
            @empty
                <li>Opened modules will appear here.</li>
            @endforelse
        </ul>
    </section>
    <details class="dashboard-panel">
        <summary>Challenge results</summary>
        @if($weekly)
            <p><a class="quiet-link" href="{{ route('weekly-events.show', $weekly) }}">This week's coding challenge: {{ $weekly->revision->title }}</a></p>
        @endif
        <ul class="compact-activity">
            @forelse($dashboard['quests']->take(3) as $quest)
                <li>
                    <a href="{{ route('challenge-attempts.show', $quest->id) }}">{{ $quest->title }}</a>
                    <span>{{ $quest->status }}</span>
                </li>
            @empty
                <li>No challenge attempts yet.</li>
            @endforelse
        </ul>
        <a class="quiet-link" href="{{ route('challenges.catalog') }}">Browse challenges</a>
    </details>
@endif
    <section class="dashboard-panel" aria-labelledby="dashboard-updates">
        <div class="section-heading">
            <h2 id="dashboard-updates">Your updates</h2>
            <a class="quiet-link" href="{{ route('notifications.index') }}">{{ $dashboard['unread'] }} unread</a>
        </div>
        @forelse($dashboard['updates'] as $update)
            <div class="lesson-note">
                <b>{{ $update->data['title'] ?? 'Account update' }}</b>
                <p>{{ $update->data['decision'] ?? '' }}{{ $update->read_at ? ' · Read' : ' · Unread' }}</p>
            </div>
        @empty
            <p class="field-hint">Review decisions and platform notices will appear here.</p>
        @endforelse
    </section>
</div>
@if($dashboard['creator'])
    <section aria-labelledby="dashboard-studio">
        <h2 id="dashboard-studio">Your creator studio</h2>
        <div class="dashboard-grid creator-dashboard-grid">
            @foreach($dashboard['creator'] as $summary)
                <article class="dashboard-panel">
                    <h3>{{ $summary['label'] }}</h3>
                    <p>{{ $summary['published'] }} published · {{ $summary['pending'] }} awaiting review · {{ $summary['drafts'] }} drafts or corrections</p>
                    <a class="button button-dark button-small" href="{{ route($summary['route']) }}">Continue creating</a>
                </article>
            @endforeach
        </div>
    </section>
@endif
