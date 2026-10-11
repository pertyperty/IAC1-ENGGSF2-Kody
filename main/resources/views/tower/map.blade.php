@extends('layouts.learning')
@section('title', 'The Kody Tower — Learn coding through play')
@section('page-class', 'learning-page tower-map-page')
@section('main-class', 'tower-map-main')
@section('footer')@endsection
@section('content')
<section class="tower-page" data-tower-map data-welcome-url="{{ route('welcome') }}" @guest data-guest-tower @endguest>
    <div class="tower-scenery" aria-hidden="true"><img data-ascent-art src="{{ asset(config('branding.assets.tower')) }}" alt=""><x-kody-art name="satellite" class="ascent-satellite" size="140"/><div class="ascent-glow"></div></div>
    <span class="tower-altitude" data-tower-altitude aria-hidden="true">Meadow · floors 1–10</span>
    <canvas class="tower-grid" data-tower-grid aria-hidden="true"></canvas>
    <div class="tower-heading tower-overlay">
        <div class="tower-title-row"><h1>Kody Tower<span class="brand-dot">.</span></h1>
            <details class="tower-guide"><summary aria-label="Tower guide"><span aria-hidden="true">i</span></summary><div><h2>Your climb</h2><p>Start small. Climb higher. Code your way through {{ $tower['total'] }} discoveries.</p><p>Scroll up, use arrow keys or press Higher to explore. Select a numbered level to play; select its information button for a preview.</p><p>Boss checkpoints every 10 levels. @guest Trial progress stays in this browser; create an account to save your climb. @else Confirmed tower wins keep your daily streak going. Tower play awards no extra XP or KodeBits. @endguest</p><a class="button button-secondary button-small" href="{{ route('help.index') }}">Visit Help →</a></div></details>
        </div>
        <div class="tower-overview">@auth<b>{{ $tower['completed_count'] }} / {{ $tower['total'] }}</b><span>levels cleared</span>@else<b>3 free trials</b><span>Join to save your climb</span>@endauth</div>
    </div>
    <div class="tower-map-tools tower-overlay">
        <div class="tower-resume">@if($tower['next'])<a class="button button-play" data-tower-continue href="{{ route('tower.show', $tower['next']) }}">{{ $tower['completed_count'] ? 'Continue' : 'Start' }} level {{ $tower['next']->position }} →</a>@endif
            @can('manage', \App\Models\TowerLevel::class)<a class="button button-secondary button-small" href="{{ route('tower-studio.index') }}">Tower studio</a>@endcan
        </div>
        <div class="tower-direction-tools"><button type="button" class="button button-secondary button-small" data-tower-scroll="up" aria-label="Climb higher" title="Climb higher">↑</button><button type="button" class="button button-secondary button-small" data-tower-present aria-label="Jump to your current level" title="Jump to your current level">◎</button><button type="button" class="button button-secondary button-small" data-tower-scroll="down" aria-label="Return to lower floors" title="Return to lower floors">↓</button></div>
    </div>
    <div class="tower-viewport" data-tower-viewport data-next-position="{{ $tower['next']?->position ?? 1 }}" tabindex="0" role="region" aria-label="Tower map. Level 1 is at the bottom. Scroll up to climb; use the Higher and Lower buttons or arrow keys.">
    <div class="tower-path" aria-label="Numbered tower levels">
    @forelse($tower['steps'] as $step)
        @php($level = $step['level'])
        @php($lane = [0, .5, 1, .5, 0, -.5, -1, -.5][($level->position - 1) % 8])
        @php($nextLane = [0, .5, 1, .5, 0, -.5, -1, -.5][$level->position % 8])

        <div class="tower-stop {{ $level->position % 10 === 0 ? 'tower-boss' : '' }} {{ $step['completed'] ? 'is-cleared' : '' }} {{ auth()->check() && !$step['unlocked'] ? 'is-locked' : '' }}" data-tower-stop="{{ $level->position }}" style="--lane: {{ $lane }}">
            @if($level->position % 5 !== 0)<svg class="tower-trail" viewBox="0 0 200 130" preserveAspectRatio="none" aria-hidden="true"><path d="M {{ 100 + $lane * 100 }} 130 C {{ 100 + $lane * 100 }} 65, {{ 100 + $nextLane * 100 }} 65, {{ 100 + $nextLane * 100 }} 0"/></svg>@endif
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
</section>
@endsection
