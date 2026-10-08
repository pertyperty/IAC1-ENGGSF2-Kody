@extends('layouts.learning')
@section('title', 'Your playground — Kody')
@section('content')
    <section class="play-hub page-width"><div class="section-heading"><div>@can('viewAny', \App\Models\User::class)<p class="overline">YOUR COMMUNITY WORKSPACE</p><h1>Everything you need to help.</h1>@else<p class="overline">A LITTLE BETTER, EVERY DAY</p><h1>Your next little win.</h1>@endcan</div><a class="quiet-link" href="{{ route('account.show') }}">View your profile →</a></div>
        @include('layouts.staff-tools')
        @can('viewLearning', \App\Models\LearningCourse::class)
        <div class="continue-card">@if($progress['next_level'])<div><p class="overline">READY WHEN YOU ARE</p><h2>{{ $progress['levels'][$progress['next_level']]['title'] }}</h2><p>{{ $progress['completed_count'] === 0 ? 'Start your first saved adventure.' : 'Your next level is unlocked. Try a new idea.' }}</p></div><a class="button button-play" href="{{ route('learning.show', $progress['next_level']) }}">Continue playing →</a>@else<div><h2>You cleared your starter trail!</h2><p>Replay a favorite or discover a creator adventure.</p></div><a class="button button-play" href="{{ route('learning.catalog') }}">Find your next adventure →</a>@endif</div>
        @can('viewLearning', \App\Models\LearningCourse::class)<p><a class="button button-dark button-small" href="{{ route('course-learning.mine') }}">Continue your learning journeys →</a></p>@endcan
        <p><a class="quiet-link" href="{{ route('challenges.catalog') }}">Explore coding quests →</a></p>
        @if($weekly)<article class="weekly-card"><div><p class="overline">THIS WEEK'S SHARED QUEST</p><h2>{{ $weekly->revision->title }}</h2><p>{{ config('challenges.languages')[$weekly->revision->language] }} · {{ $weekly->revision->difficulty }} · Three fresh attempts</p><p>Closes {{ $weekly->ends_at->setTimezone('Asia/Manila')->format('M j · g:i a') }} Manila time.</p></div><a class="button button-play" href="{{ route('weekly-events.show', $weekly) }}">Explore this week's quest →</a></article>@endif
        <p><a class="quiet-link" href="{{ route('weekly-events.index') }}">Your weekly quest history →</a></p>
        @can('create', \App\Models\CodingChallenge::class)<p><a class="quiet-link" href="{{ route('challenges.index') }}">Your challenge studio →</a></p>@endcan
        <div id="daily-progress" class="progress-stats"><div><span>Daily streak</span><b>{{ $progress['current_streak'] }} <small>{{ $progress['current_streak'] === 1 ? 'day' : 'days' }}</small></b><p>{{ $progress['active_today'] ? 'Today’s practice is saved. Nicely done!' : 'Complete a game or quiz today.' }}</p></div><div><span>Longest streak</span><b>{{ $progress['longest_streak'] }} <small>{{ $progress['longest_streak'] === 1 ? 'day' : 'days' }}</small></b><p>A little reminder of what you can do.</p></div><div><span>Your ladder</span><b>{{ $progress['completed_count'] }} <small>/ {{ count($progress['levels']) }} levels</small></b><p>Clear a game to unlock the next.</p></div></div>
        <p class="catalog-note">Your day resets at midnight in Manila. Miss a day and start a new streak. You can replay a cleared game to keep learning.</p>
        @endcan
        @include('learning.dashboard-panels')
        @can('viewLearning', \App\Models\LearningCourse::class)
        <div id="level-trail" class="level-ladder">@foreach($progress['levels'] as $slug => $level)<article class="ladder-card {{ $level['color'] }} {{ $level['unlocked'] ? '' : 'level-locked' }}"><div class="level-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div><div><span class="module-meta">{{ $level['concept'] }}</span><h2>{{ $level['title'] }}</h2><p>{{ $level['description'] }}</p></div><div>@if($level['unlocked'])<a class="button button-dark button-small" href="{{ route('learning.show', $slug) }}">{{ $level['completed'] ? 'Play again' : 'Let’s play' }} →</a><span class="level-state">{{ $level['completed'] ? 'Cleared ✓' : 'Ready to explore' }}</span>@else<span class="level-state">Clear the previous level to unlock</span>@endif</div></article>@endforeach</div>
        <p class="catalog-note">Clear a level to save your progress and keep your streak growing. Your first verified game clear and quiz win on each starter level earn 20 XP each. Local practice earns no rewards.</p>
        @endcan
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="quiet-link hub-logout">Sign out</button></form>
    </section>
    <section class="page-width"><h2>Try another kind of puzzle.</h2><p>Explore four practice templates, including a command-line mission.</p><a class="button button-dark" href="{{ route('arcade') }}">Open the playground →</a></section>
@endsection
