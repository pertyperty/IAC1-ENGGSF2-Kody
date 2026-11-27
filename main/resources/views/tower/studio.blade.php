@extends('layouts.learning')
@section('title', 'Tower studio — Kody')
@section('content')
<section class="review-page page-width"><div class="studio-heading"><div><p class="overline">THE MAIN ADVENTURE</p><h1>Tower studio</h1><p>Shape the next discovery. Every revision preserves earlier clears and its original learning content.</p></div></div>
@php
    $activeTrack = request()->query('track');
@endphp
<nav class="filter-bar" aria-label="Tower studio tracks"><a class="button button-secondary button-small" href="{{ route('tower-studio.index') }}" @if(!$activeTrack) aria-current="page" @endif>All tracks</a>@foreach(app(\App\Services\Gamification\TowerTracks::class)->all() as $key => $track)<a class="button button-secondary button-small" href="{{ route('tower-studio.index', ['track' => $key]) }}" @if($activeTrack === $key) aria-current="page" @endif>{{ $track['label'] }}</a>@endforeach</nav>
<div class="studio-library-grid">@foreach($levels as $level)<a class="tower-editor-card" href="{{ route('tower-studio.edit', $level) }}"><span>@if($level->position % 10 === 0)<x-action-icon name="crown"/> BOSS @else LEVEL @endif {{ $level->position }} · {{ app(\App\Services\Gamification\TowerTracks::class)->for($level)['label'] }}</span><h2>{{ $level->currentRevision?->title ?? 'Not configured' }}</h2><p>{{ $level->currentRevision?->concept }} · {{ count($level->currentRevision?->stages ?? []) }} stages · v{{ $level->record_version }}</p><b>Edit level</b></a>@endforeach</div>{{ $levels->links() }}</section>
@endsection
