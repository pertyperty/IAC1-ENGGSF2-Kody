@extends('layouts.learning')
@section('title', 'Your workspace — Kody')
@section('content')
    <section class="play-hub page-width"><div class="section-heading"><div>@if(auth()->user()->can('viewAny', \App\Models\User::class))<p class="overline">YOUR COMMUNITY WORKSPACE</p><h1>Everything you need to help.</h1>@elseif(!$progress)<p class="overline">YOUR CREATOR WORKSPACE</p><h1>Make learning worth playing.</h1>@else<p class="overline">MY PROGRESS</p><h1>Progress</h1>@endif</div><a class="quiet-link" href="{{ route('account.show') }}">View your profile</a></div>
        @include('layouts.staff-tools')
        @if(auth()->user()->account_role === \App\Enums\Role::Instructor)<div class="creator-play-link"><div><h2>Explore the Tower</h2><p>Explore the Tower as a learner. Your confirmed clears save your own progress and daily streak.</p></div><a class="button button-play" href="{{ route('home') }}"><x-action-icon name="tower"/> Play the Tower</a></div>@endif
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
        <details class="content-panel optional-starter-lessons"><summary>Optional starter modules</summary><p>Three optional introductory modules. Your saved results stay here.</p><div id="level-trail" class="level-ladder">@foreach($progress['levels'] as $slug => $level)<article class="ladder-card {{ $level['color'] }} {{ $level['unlocked'] ? '' : 'level-locked' }}"><div class="level-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div><div><span class="module-meta">{{ $level['concept'] }}</span><h2>{{ $level['title'] }}</h2><p>{{ $level['concept'] }}</p></div><div>@if($level['unlocked'])<a class="button button-dark button-small" href="{{ route('learning.show', $slug) }}">{{ $level['completed'] ? 'Play again' : 'Let’s play' }}</a><span class="level-state">{{ $level['completed'] ? 'Cleared' : 'Ready to explore' }}</span>@else<span class="level-state">Clear the previous level to unlock</span>@endif</div></article>@endforeach</div>
        <p class="catalog-note">Clear a level to save your progress and keep your streak growing. Your first verified game clear and quiz win on each starter level earn 20 XP each. Local previews earn no rewards.</p></details>
        @endif
        @include('learning.dashboard-panels')
        @if($insights)@include('learning.creator-insights')@endif
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="quiet-link hub-logout">Sign out</button></form>
    </section>
@endsection
