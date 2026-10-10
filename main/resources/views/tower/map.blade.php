@extends('layouts.learning')
@section('title', 'The Kody Tower — Learn coding through play')
@section('content')
<section class="tower-page page-width" data-tower-map data-welcome-url="{{ route('welcome') }}" @guest data-guest-tower @endguest>
    <div class="tower-heading"><div><p class="overline">ONE LEVEL. ONE NEW IDEA.</p><h1>The Kody Tower<span class="brand-dot">.</span></h1><p>Start small. Climb higher. Code your way through {{ $tower['total'] }} discoveries.</p></div>
        <div class="tower-overview">@auth<b>{{ $tower['completed_count'] }} / {{ $tower['total'] }}</b><span>levels cleared</span>@else<b>3 free trials</b><span>Play first. Join when you’re ready.</span>@endauth</div>
    </div>
    @if($tower['next'])<div class="tower-resume"><a class="button button-play" data-tower-continue href="{{ route('tower.show', $tower['next']) }}">{{ $tower['completed_count'] ? 'Continue' : 'Start' }} level {{ $tower['next']->position }} →</a></div>@endif
    @can('manage', \App\Models\TowerLevel::class)<a class="button button-secondary" href="{{ route('tower-studio.index') }}">Open tower studio</a>@endcan
    <div class="tower-path" aria-label="Numbered tower levels">
    @forelse($tower['steps'] as $step)
        @php($level = $step['level'])
        @if(($level->position - 1) % 5 === 0)<div class="tower-chapter"><span>{{ str_pad((string) (int) ceil($level->position / 5), 2, '0', STR_PAD_LEFT) }}</span><h2>{{ ['First sparks','Logic lands','Data district','The algorithm vault','The next horizon'][(int) ceil($level->position / 5) - 1] ?? 'Higher ground' }}</h2></div>@endif
        <div class="tower-stop {{ $level->position % 10 === 0 ? 'tower-boss' : '' }} {{ $step['completed'] ? 'is-cleared' : '' }} {{ auth()->check() && !$step['unlocked'] ? 'is-locked' : '' }}" data-tower-stop="{{ $level->position }}">
            <a class="tower-node" href="{{ route('tower.show', $level) }}" @if(auth()->check() && !$step['unlocked']) aria-disabled="true" tabindex="-1" @endif aria-label="Level {{ $level->position }}: {{ $level->currentRevision->title }}{{ $step['completed'] ? ', cleared' : '' }}{{ auth()->check() && !$step['unlocked'] ? ', locked' : '' }}">
                @if($level->position % 10 === 0)<span class="boss-crown" aria-hidden="true">♛</span>@endif<span>{{ $level->position }}</span><i aria-hidden="true">{{ $step['completed'] ? '✓' : ($level->position % 10 === 0 ? 'BOSS' : 'PLAY') }}</i>
            </a>
            <div class="tower-label"><b>{{ $level->currentRevision->title }}</b><span>{{ $level->currentRevision->concept }}</span></div>
        </div>
    @empty<div class="empty-state"><h2>Your next adventure is on its way.</h2><p>The tower curriculum has not been installed yet.</p></div>@endforelse
    </div>
    <p class="tower-footnote">Boss checkpoints at levels 10 and 20. @guest Trial progress stays in this browser; create an account to save your climb. @else Confirmed tower wins keep your daily streak going. Tower play awards no extra XP or KodeBits. @endguest</p>
</section>
@endsection
