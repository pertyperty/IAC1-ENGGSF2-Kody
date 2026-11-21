@extends('layouts.learning')
@section('title', 'Tower levels - Kody')
@section('content')
@include('tower.proficiency')
@pushOnce('page-scripts', 'tower-page')
    @vite('resources/js/tower-page.js')
@endPushOnce
<section class="page-width tower-list-page" aria-labelledby="tower-list-heading" data-tower-list @guest data-guest-tower @endguest>
    <div class="workspace-heading">
        <div><p class="overline">Kody Tower</p><h1 id="tower-list-heading">Tower levels</h1></div>
        <a class="button button-secondary" href="{{ route('home', ['track' => $tower['selected_track'], 'view' => 'map']) }}">Map view</a>
    </div>
    <nav class="studio-wayfinding" aria-label="Tower tracks">
        @foreach($tower['tracks'] as $key => $track)
        <a href="{{ auth()->check() || $key === 'beginner' ? route('home', ['track' => $key, 'view' => 'list']) : route('welcome') }}" @if($tower['selected_track'] === $key) aria-current="page" @endif>{{ $track['label'] }}</a>
        @endforeach
    </nav>
    <h2>{{ $tower['tracks'][$tower['selected_track']]['label'] }}</h2>
    <p>@auth {{ $tower['track_counts'][$tower['selected_track']]['completed'] }} of {{ $tower['track_counts'][$tower['selected_track']]['total'] }} cleared. Clear each level to unlock the next in this track. @else Try the first three levels. Sign in to save progress. @endauth</p>
    <ol class="tower-level-list" aria-label="{{ $tower['tracks'][$tower['selected_track']]['label'] }} levels">
        @forelse($tower['steps']->where('track', $tower['selected_track']) as $step)
        @php
            $level = $step['level'];
            $locked = auth()->check() && !$step['unlocked'];
            $isNext = $tower['next']?->id === $level->id;
        @endphp
        <li data-tower-list-level="{{ $level->position }}" @if($isNext) aria-current="step" @endif>
            <span class="tower-list-number" aria-hidden="true">{{ $level->position }}</span>
            <div>
                @if($locked)
                <span class="tower-list-title">Level {{ $level->position }}: {{ $level->currentRevision->title }}</span>
                @else
                <a class="tower-list-title" href="{{ auth()->guest() && $level->position > 3 ? route('welcome') : route('tower.show', $level) }}">Level {{ $level->position }}: {{ $level->currentRevision->title }}</a>
                @endif
                <p>{{ $level->currentRevision->concept }}</p>
            </div>
            <span class="tower-list-status" data-tower-list-status>{{ $step['completed'] ? 'Cleared' : ($locked ? 'Locked' : (auth()->guest() && $level->position > 3 ? 'Sign in' : ($isNext ? 'Up next' : 'Ready'))) }}</span>
        </li>
        @empty
        <li><p>No published levels in this track yet.</p></li>
        @endforelse
    </ol>
</section>
@endsection
