@extends('layouts.learning')
@section('title', 'Your workspace — Kody')
@section('content')
    <section class="play-hub page-width">
        <header class="section-heading">
            <h1>{{ auth()->user()->can('viewAny', \App\Models\User::class) ? 'Staff dashboard' : (!$progress ? 'Creator dashboard' : 'Progress') }}</h1>
            <a class="quiet-link" href="{{ route('account.show') }}">View your profile</a>
        </header>
        @include('layouts.staff-tools')
        @if(auth()->user()->account_role === \App\Enums\Role::Instructor)<div class="creator-play-link"><div><h2>Explore the Tower</h2><p>Play levels and track your own progress.</p></div><a class="button button-play" href="{{ route('home') }}"><x-action-icon name="tower"/> Play the Tower</a></div>@endif
        @if($progress)
        <section class="content-panel tower-continuation" aria-labelledby="continue-heading">
            <p class="overline">{{ $continuation['label'] }}</p><h2 id="continue-heading">Next Tower level</h2>
            <p>{{ $continuation['completed'] }} of {{ $continuation['total'] }} levels cleared in this track.</p>
            @if($continuation['next'])
                <a class="button button-play" data-primary-continue href="{{ $continuation['next']['url'] }}">Continue level {{ $continuation['next']['position'] }}</a>
            @else
                <p>{{ $continuation['total'] ? 'This track is cleared.' : 'This track is not available yet.' }}</p>
                <a class="button button-play" data-primary-continue href="{{ $continuation['map_url'] }}">Choose a Tower track</a>
            @endif

        </section>
        @if(auth()->user()->can('create', \App\Models\CodingChallenge::class))<p><a class="quiet-link" href="{{ route('challenges.index') }}">Your challenge studio</a></p>@endif
        <dl id="daily-progress" class="learning-stats"><div><dt>Streak</dt><dd>{{ $progress['current_streak'] }} days</dd></div><div><dt>Best streak</dt><dd>{{ $progress['longest_streak'] }} days</dd></div><div><dt>XP</dt><dd>{{ number_format($dashboard['achievements']['xp']) }}</dd></div><div><dt>Rank</dt><dd>{{ $dashboard['achievements']['rank'] }}</dd></div></dl>
        <p class="activity-today">{{ $progress['active_today'] ? 'Today is complete.' : 'Complete a Tower level or module assessment to keep your streak.' }}</p>
        <nav class="studio-wayfinding" aria-label="Continue learning"><a href="{{ route('course-learning.mine') }}">Library</a><a href="{{ route('home') }}">Tower</a><a href="{{ route('challenges.catalog') }}">Challenges</a></nav>
        <details class="progress-rules"><summary>How progress works</summary><p>Streaks count eligible activity each day, resetting at midnight in Manila. Tower clears save your climb without extra XP or KodeBits. Eligible module and challenge completions earn XP.</p><a class="quiet-link" href="{{ route('help.index') }}">Read progress rules</a></details>
        @endif
        @if($progress)
        <details class="content-panel optional-starter-lessons"><summary>Optional starter modules</summary><div id="level-trail" class="level-ladder">@foreach($progress['levels'] as $slug => $level)<article class="ladder-card {{ $level['color'] }} {{ $level['unlocked'] ? '' : 'level-locked' }}"><div class="level-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div><div><span class="module-meta">{{ $level['concept'] }}</span><h2>{{ $level['title'] }}</h2></div><div>@if($level['unlocked'])<a class="button button-dark button-small" href="{{ route('learning.show', $slug) }}">{{ $level['completed'] ? 'Play again' : 'Let’s play' }}</a><span class="level-state">{{ $level['completed'] ? 'Cleared' : 'Ready to explore' }}</span>@else<span class="level-state">Clear the previous level to unlock</span>@endif</div></article>@endforeach</div>
        <p class="catalog-note">Earn 20 XP for each first verified game clear or quiz win.</p></details>
        @endif
        @include('learning.dashboard-panels')
        @if($insights)@include('learning.creator-insights')@endif
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="quiet-link hub-logout">Sign out</button></form>
    </section>
@endsection
