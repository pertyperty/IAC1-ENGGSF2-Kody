@if($chrome['progress'])
<nav class="progress-strip" aria-label="Your learning progress" data-progress-strip data-progress-url="{{ route('play.progress') }}">
    <a href="{{ route('dashboard') }}#daily-progress" class="progress-streak"><span aria-hidden="true">✦</span><b data-progress-streak>{{ $chrome['progress']['current_streak'] }}</b><span>day streak</span><span class="progress-today" data-progress-today>{{ $chrome['progress']['active_today'] ? 'Today saved' : 'Play today' }}</span></a>
    <a href="{{ route('leaderboards.index') }}"><b data-progress-xp>{{ number_format($chrome['achievements']['xp']) }}</b><span>XP</span><span class="progress-rank" data-progress-rank>{{ $chrome['achievements']['rank'] }}</span></a>
    <a href="{{ route('dashboard') }}#level-trail"><b data-progress-level>{{ $chrome['progress']['completed_count'] }} / {{ count($chrome['progress']['levels']) }}</b><span>starter levels</span></a>
    <button type="button" data-progress-retry hidden>Refresh progress</button>
</nav>
@endif
