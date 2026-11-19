@extends('layouts.learning')
@section('title', 'Game presets — Kody')
@section('content')
<section class="review-page page-width"><h1>Game presets</h1><p>Reusable games and quizzes for lesson creators.</p><a class="button button-play" href="{{ route('game-presets.create') }}">Create preset</a>
@forelse($presets as $preset)<p><a class="quiet-link" href="{{ route('game-presets.edit', $preset) }}">{{ $preset->name }}</a> · {{ $preset->status }} · v{{ $preset->currentRevision->number }}</p>@empty<p>No saved presets yet. Creators can still use the built-in activities.</p>@endforelse{{ $presets->links() }}</section>
@endsection
