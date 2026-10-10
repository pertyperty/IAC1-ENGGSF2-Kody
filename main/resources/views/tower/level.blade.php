@extends('layouts.learning')
@section('title', 'Level '.$level->position.' · '.$revision->title.' — Kody')
@section('content')
<section class="tower-level page-width" data-tower-level="{{ $level->position }}" data-map-url="{{ route('home') }}" data-welcome-url="{{ route('welcome') }}" @guest data-guest-tower @endguest>
    <div class="section-heading"><div><p class="overline">{{ $level->position % 10 === 0 ? 'BOSS CHECKPOINT' : 'TOWER LEVEL' }} {{ $level->position }}</p><h1>{{ $revision->title }}</h1><p>{{ $revision->description }}</p></div><a class="button button-secondary" href="{{ route('home') }}">← Level map</a></div>
    <nav class="tower-stage-tabs" aria-label="Level stages">@foreach($revision->stages as $instance)<a data-tower-stage-link="{{ $loop->index }}" href="#tower-stage-{{ $loop->index }}" @if($loop->index > 0 && !in_array($loop->index - 1, $done, true)) aria-disabled="true" tabindex="-1" @endif>{{ $loop->iteration }}. {{ $instance['title'] }}</a>@endforeach</nav>
    <div class="lesson-note" data-tower-locked hidden>Clear the previous trial before opening this level. <a class="button button-secondary button-small" href="{{ route('home') }}">Return to the map</a></div>
    <div data-tower-stages>
    @foreach($revision->stages as $instance)
        <section id="tower-stage-{{ $loop->index }}" class="tower-stage" data-tower-stage="{{ $loop->index }}" @if($loop->index > 0 && !in_array($loop->index - 1, $done, true)) hidden @endif>
            <p class="step-pill">Stage {{ $loop->iteration }} / {{ $loop->count }}</p>
            @php($completionUrl = auth()->check() ? route('tower.complete', [$level, $revision->id, $loop->index]) : null)
            @if($instance['template'] === 'choice-quiz')@include('learning.quiz', ['quiz' => $instance, 'module' => null, 'towerActivity' => true])
            @else @include('learning.activity', ['game' => $instance, 'module' => null, 'trial' => false]) @endif
        </section>
    @endforeach
    </div>
    <div class="tower-victory" data-tower-victory hidden><span aria-hidden="true">✦</span><div><h2>Another step up!</h2><p data-tower-result></p></div><a class="button button-play" data-tower-next href="{{ route('home') }}">Continue climbing →</a></div>
    <aside class="tower-study"><div><b>Want to understand the idea?</b><p>Explore creator lessons about {{ $revision->concept }}. Learning is always another way forward.</p></div><a class="button button-secondary" href="{{ route('learning.catalog', ['q' => $revision->concept]) }}">Explore lessons →</a></aside>
    <p class="field-hint">@guest These trials stay in this browser and grant no saved progress or rewards. @else Kody validates each stage before saving the clearance. Complete the final stage for daily streak credit; tower clears grant no additional XP or KodeBits. @endguest</p>
</section>
@endsection
