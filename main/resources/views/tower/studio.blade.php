@extends('layouts.learning')
@section('title', 'Tower studio — Kody')
@section('content')
<section class="review-page page-width"><div class="studio-heading"><div><p class="overline">THE MAIN ADVENTURE</p><h1>Tower studio</h1><p>Shape the next discovery. Every revision preserves earlier clears and its original learning content.</p></div></div>
<div class="studio-library-grid">@foreach($levels as $level)<a class="tower-editor-card" href="{{ route('tower-studio.edit', $level) }}"><span>{{ $level->position % 10 === 0 ? '♛ BOSS' : 'LEVEL' }} {{ $level->position }}</span><h2>{{ $level->currentRevision?->title ?? 'Not configured' }}</h2><p>{{ $level->currentRevision?->concept }} · {{ count($level->currentRevision?->stages ?? []) }} stages · v{{ $level->record_version }}</p><b>Edit level →</b></a>@endforeach</div>{{ $levels->links() }}</section>
@endsection
