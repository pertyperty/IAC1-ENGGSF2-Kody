@extends('layouts.learning')
@section('title', 'Your playground — Kody')
@section('content')
    <section class="play-hub page-width"><div class="section-heading"><div><p class="overline">A LITTLE BETTER, EVERY DAY</p><h1>Your next little win.</h1></div><a class="quiet-link" href="{{ route('account.show') }}">View your profile →</a></div>
        <div class="progress-stats"><div><span>Daily streak</span><b>{{ $progress['current_streak'] }} <small>{{ $progress['current_streak'] === 1 ? 'day' : 'days' }}</small></b><p>Complete a game or quiz today.</p></div><div><span>Longest streak</span><b>{{ $progress['longest_streak'] }} <small>days</small></b><p>A little reminder of what you can do.</p></div><div><span>Your ladder</span><b>{{ $progress['completed_count'] }} <small>/ {{ count($progress['levels']) }} levels</small></b><p>Clear a game to unlock the next.</p></div></div>
        <p class="catalog-note">Your day resets at midnight in Manila. Miss a day and start a new streak. You can replay a cleared game to keep learning.</p>
        <div class="level-ladder">@foreach($progress['levels'] as $slug => $level)<article class="ladder-card {{ $level['color'] }} {{ $level['unlocked'] ? '' : 'level-locked' }}"><div class="level-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div><div><span class="module-meta">{{ $level['concept'] }}</span><h2>{{ $level['title'] }}</h2><p>{{ $level['description'] }}</p></div><div>@if($level['unlocked'])<a class="button button-dark button-small" href="{{ route('learning.show', $slug) }}">{{ $level['completed'] ? 'Play again' : 'Let’s play' }} →</a><span class="level-state">{{ $level['completed'] ? 'Cleared ✓' : 'Ready to explore' }}</span>@else<span class="level-state">Clear the previous level to unlock</span>@endif</div></article>@endforeach</div>
        <p class="catalog-note">These wins save your level progress and daily activity. XP, ranks and KodeBits remain separate and are not awarded by these practice templates.</p>
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="quiet-link hub-logout">Sign out</button></form>
    </section>
@endsection
