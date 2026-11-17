@extends('layouts.learning')
@section('title', 'Level '.$level->position.' · '.$revision->title.' — Kody')
@section('content')
<section class="tower-level page-width" data-tower-level="{{ $level->position }}" data-map-url="{{ route('home', ['track' => app(\App\Services\Gamification\TowerTracks::class)->key($level)]) }}" data-welcome-url="{{ route('welcome') }}" @guest data-guest-tower @endguest>
    <p class="field-hint">{{ app(\App\Services\Gamification\TowerTracks::class)->for($level)['label'] }} · Level {{ $level->position }}</p>
    <div class="section-heading"><div><p class="overline">{{ $level->position % 10 === 0 ? 'BOSS CHECKPOINT' : 'TOWER LEVEL' }} {{ $level->position }}</p><h1>{{ $revision->title }}</h1><p>{{ $revision->description }}</p></div><a class="button button-secondary" href="{{ route('home', ['track' => app(\App\Services\Gamification\TowerTracks::class)->key($level)]) }}"><x-action-icon name="left"/> Level map</a></div>
    @auth<form method="post" action="{{ route('review-queue.update', $level) }}" class="studio-actions">@csrf<input type="hidden" name="action" value="add"><button class="button button-secondary button-small">Review later</button><a href="{{ route('review-queue.index') }}">My review list</a></form>@endauth
    @if(count($revision->stages) > 1)<nav class="tower-stage-tabs" aria-label="Level stages">@foreach($revision->stages as $instance)<a data-tower-stage-link="{{ $loop->index }}" href="#tower-stage-{{ $loop->index }}" @if($loop->index > 0 && !in_array($loop->index - 1, $done, true)) aria-disabled="true" tabindex="-1" @endif>{{ $loop->iteration }}. {{ $instance['title'] }}</a>@endforeach</nav>@endif
    <div class="lesson-note" data-tower-locked hidden>Clear the previous trial before opening this level. <a class="button button-secondary button-small" href="{{ route('home') }}">Return to the map</a></div>
    <div data-tower-stages>
    @foreach($revision->stages as $instance)
        <section id="tower-stage-{{ $loop->index }}" class="tower-stage" data-tower-stage="{{ $loop->index }}" @if($loop->index > 0 && !in_array($loop->index - 1, $done, true)) hidden @endif>
            @if($loop->count > 1)<p class="step-pill">Stage {{ $loop->iteration }} / {{ $loop->count }}</p>@endif
            @php($completionUrl = auth()->check() ? route('tower.complete', [$level, $revision->id, $loop->index]) : null)
            @if($instance['template'] === 'choice-quiz')@include('learning.quiz', ['quiz' => $instance, 'module' => null, 'towerActivity' => true])
            @else @include('learning.activity', ['game' => $instance, 'module' => null, 'trial' => false]) @endif
        </section>
    @endforeach
    </div>
    <div class="tower-victory" data-tower-victory hidden><span aria-hidden="true"><x-action-icon name="spark"/></span><div><h2>Another step up!</h2><p data-tower-result></p></div><a class="button button-play" data-tower-next href="{{ route('home') }}">Continue climbing <x-action-icon name="right"/></a></div>
    <aside class="tower-study"><div><b>More on {{ $revision->concept }}</b></div><a class="button button-secondary" href="{{ route('learning.catalog', ['q' => $revision->concept]) }}">Explore lessons <x-action-icon name="right"/></a></aside>
    <p class="field-hint">@guest These trials stay in this browser and grant no saved progress or rewards. @else Kody validates each stage before saving the clearance. Complete the final stage for daily streak credit; tower clears grant no additional XP or KodeBits. @endguest</p>
    <p><a class="quiet-link" href="{{ route('contact', ['lesson' => 'Tower level '.$level->position, 'revision' => $revision->id]) }}">Report a lesson issue <x-action-icon name="right"/></a></p>
</section>
@endsection
