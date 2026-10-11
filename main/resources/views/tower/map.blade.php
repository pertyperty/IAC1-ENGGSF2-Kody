@extends('layouts.learning')
@section('title', 'The Kody Tower — Learn coding through play')
@section('content')
<section class="tower-page page-width" data-tower-map data-welcome-url="{{ route('welcome') }}" @guest data-guest-tower @endguest>
    <div class="tower-heading"><div><p class="overline">ONE LEVEL. ONE NEW IDEA.</p><h1>The Kody Tower<span class="brand-dot">.</span></h1><p>Start small. Climb higher. Code your way through {{ $tower['total'] }} discoveries.</p></div>
        <div class="tower-overview">@auth<b>{{ $tower['completed_count'] }} / {{ $tower['total'] }}</b><span>levels cleared</span>@else<b>3 free trials</b><span>Play first. Join when you’re ready.</span>@endauth</div>
    </div>
    @if($tower['next'])<div class="tower-resume"><a class="button button-play" data-tower-continue href="{{ route('tower.show', $tower['next']) }}">{{ $tower['completed_count'] ? 'Continue' : 'Start' }} level {{ $tower['next']->position }} →</a></div>@endif
    @can('manage', \App\Models\TowerLevel::class)<a class="button button-secondary" href="{{ route('tower-studio.index') }}">Open tower studio</a>@endcan
    <div class="tower-map-tools"><span>Climb from the first floor to the summit</span><div><button type="button" class="button button-secondary button-small" data-tower-scroll="up" aria-label="Climb higher">↑ Higher</button><button type="button" class="button button-quiet button-small" data-tower-scroll="down" aria-label="Return to lower floors">↓ Lower</button></div></div>
    <div class="tower-viewport" data-tower-viewport data-next-position="{{ $tower['next']?->position ?? 1 }}" tabindex="0" role="region" aria-label="Tower map. Level 1 is at the bottom. Scroll up to climb; use the Higher and Lower buttons or arrow keys.">
    <div class="tower-path" aria-label="Numbered tower levels">
    @forelse($tower['steps'] as $step)
        @php($level = $step['level'])

        <div class="tower-stop {{ $level->position % 10 === 0 ? 'tower-boss' : '' }} {{ $step['completed'] ? 'is-cleared' : '' }} {{ auth()->check() && !$step['unlocked'] ? 'is-locked' : '' }}" data-tower-stop="{{ $level->position }}">
            <a class="tower-node" href="{{ route('tower.show', $level) }}" @if(auth()->check() && !$step['unlocked']) aria-disabled="true" tabindex="-1" @endif aria-label="Level {{ $level->position }}: {{ $level->currentRevision->title }}{{ $step['completed'] ? ', cleared' : '' }}{{ auth()->check() && !$step['unlocked'] ? ', locked' : '' }}">
                @if($level->position % 10 === 0)<span class="boss-crown" aria-hidden="true">♛</span>@endif<span>{{ $level->position }}</span><i aria-hidden="true">{{ $step['completed'] ? '✓' : ($level->position % 10 === 0 ? 'BOSS' : 'PLAY') }}</i>
            </a>
            <button type="button" class="tower-info" aria-label="About level {{ $level->position }}" aria-expanded="false" aria-controls="tower-detail-{{ $level->position }}">i</button>
            <div class="tower-label" id="tower-detail-{{ $level->position }}"><b>{{ $level->currentRevision->title }}</b><span>{{ $level->currentRevision->concept }}</span><p>{{ $level->currentRevision->description }}</p></div>
        </div>
        @if($level->position % 5 === 0)<div class="tower-chapter"><span>{{ str_pad((string) (int) ceil($level->position / 5), 2, '0', STR_PAD_LEFT) }}</span><h2>{{ ['First sparks','Logic lands','Data district','The algorithm vault','The next horizon','Signal station','Memory islands','Systems workshop','Code constellation','The summit'][(int) ceil($level->position / 5) - 1] ?? 'Higher ground' }}</h2></div>@endif
    @empty<div class="empty-state"><h2>Your next adventure is on its way.</h2><p>The tower curriculum has not been installed yet.</p></div>@endforelse
    </div>
    </div>
    <p class="tower-footnote">Boss checkpoints every 10 levels. @guest Trial progress stays in this browser; create an account to save your climb. @else Confirmed tower wins keep your daily streak going. Tower play awards no extra XP or KodeBits. @endguest</p>
</section>
@endsection
