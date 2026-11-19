@extends('layouts.learning')
@section('title', 'Your workspace — Kody')
@section('content')
    <section class="play-hub page-width"><div class="section-heading"><div>@if(auth()->user()->can('viewAny', \App\Models\User::class))<p class="overline">YOUR COMMUNITY WORKSPACE</p><h1>Everything you need to help.</h1>@elseif(!$progress)<p class="overline">YOUR CREATOR WORKSPACE</p><h1>Make learning worth playing.</h1>@else<p class="overline">MY PROGRESS</p><h1>Continue learning.</h1>@endif</div><a class="quiet-link" href="{{ route('account.show') }}">View your profile</a></div>
        @include('layouts.staff-tools')
        @if(auth()->user()->account_role === \App\Enums\Role::Instructor)<div class="creator-play-link"><div><h2>A little play inspires great lessons</h2><p>Explore the Tower as a learner. Your confirmed clears save your own progress and daily streak.</p></div><a class="button button-play" href="{{ route('home') }}"><x-action-icon name="tower"/> Play the Tower</a></div>@endif
        @if($progress)
        <section class="content-panel tower-continuation" aria-labelledby="continue-heading">
            <p class="overline">{{ $continuation['label'] }}</p><h2 id="continue-heading">Your next Tower level</h2>
            <p>{{ $continuation['completed'] }} of {{ $continuation['total'] }} levels cleared in this track.</p>
            @if($continuation['next'])
                <a class="button button-play" data-primary-continue href="{{ $continuation['next']['url'] }}">Continue level {{ $continuation['next']['position'] }}</a>
            @else
                <p>{{ $continuation['total'] ? 'This track is cleared.' : 'This track is not available yet.' }}</p>
                <a class="button button-play" data-primary-continue href="{{ $continuation['map_url'] }}">Choose a Tower track</a>
            @endif
            <p class="field-hint">A confirmed final-stage clear saves Tower progress and counts toward your daily streak. Tower clears grant no extra XP or KodeBits.</p>
        </section>
        <details class="content-panel"><summary>Other practice and coding challenges</summary>
            <p><a class="quiet-link" href="{{ route('arcade') }}">Practice arcade</a> · <a class="quiet-link" href="{{ route('challenges.catalog') }}">Coding challenges</a></p>
            @if($weekly)<p><a class="quiet-link" href="{{ route('weekly-events.show', $weekly) }}">This week's coding challenge: {{ $weekly->revision->title }}</a></p>@endif
            <p><a class="quiet-link" href="{{ route('weekly-events.index') }}">Weekly challenge history</a></p>
        </details>
        @if(auth()->user()->can('create', \App\Models\CodingChallenge::class))<p><a class="quiet-link" href="{{ route('challenges.index') }}">Your challenge studio</a></p>@endif
        <div id="daily-progress" class="progress-stats"><div><span>Daily streak</span><b>{{ $progress['current_streak'] }} <small>{{ $progress['current_streak'] === 1 ? 'day' : 'days' }}</small></b><p>{{ $progress['active_today'] ? 'Today’s practice is saved. Nicely done!' : 'Clear a Tower level or a lesson assessment today.' }}</p></div><div><span>Longest streak</span><b>{{ $progress['longest_streak'] }} <small>{{ $progress['longest_streak'] === 1 ? 'day' : 'days' }}</small></b><p>Your longest saved streak.</p></div></div>
        <p class="catalog-note">Your day resets at midnight in Manila. Miss a day and start a new streak. You can replay a cleared game to keep learning.</p>
        @endif
        @if($progress)
        <details class="content-panel optional-starter-lessons"><summary>Optional starter lessons</summary><p>These three introductions keep their own saved history. They are supporting practice, separate from your Tower track.</p><div id="level-trail" class="level-ladder">@foreach($progress['levels'] as $slug => $level)<article class="ladder-card {{ $level['color'] }} {{ $level['unlocked'] ? '' : 'level-locked' }}"><div class="level-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div><div><span class="module-meta">{{ $level['concept'] }}</span><h2>{{ $level['title'] }}</h2><p>{{ $level['description'] }}</p></div><div>@if($level['unlocked'])<a class="button button-dark button-small" href="{{ route('learning.show', $slug) }}">{{ $level['completed'] ? 'Play again' : 'Let’s play' }}</a><span class="level-state">{{ $level['completed'] ? 'Cleared' : 'Ready to explore' }}</span>@else<span class="level-state">Clear the previous level to unlock</span>@endif</div></article>@endforeach</div>
        <p class="catalog-note">Clear a level to save your progress and keep your streak growing. Your first verified game clear and quiz win on each starter level earn 20 XP each. Local previews earn no rewards.</p></details>
        @endif
        @include('learning.dashboard-panels')
        @if($insights)@include('learning.creator-insights')@endif
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="quiet-link hub-logout">Sign out</button></form>
    </section>
@endsection
