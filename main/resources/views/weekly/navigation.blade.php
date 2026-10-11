<nav class="quest-navigation" aria-label="Quest and leaderboard navigation">
    <a class="button button-secondary button-small" href="{{ route('weekly-events.index') }}" @if(request()->routeIs('weekly-events.*')) aria-current="page" @endif><x-action-icon name="game"/> Weekly quests</a>
    <a class="button button-secondary button-small" href="{{ route('weekly-leaderboards.index') }}" @if(request()->routeIs('weekly-leaderboards.*', 'weekly-results.show')) aria-current="page" @endif><x-action-icon name="crown"/> Weekly leaderboard</a>
    <a class="button button-secondary button-small" href="{{ route('leaderboards.index') }}" @if(request()->routeIs('leaderboards.index')) aria-current="page" @endif><x-action-icon name="chart"/> Learning XP</a>
</nav>
