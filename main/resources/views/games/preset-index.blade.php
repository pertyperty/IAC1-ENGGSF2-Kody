@extends('layouts.learning')
@section('title', 'Game preset workshop — Kody')
@section('content')
<section class="review-page page-width"><h1>Build the next little adventure.</h1><p>Save reusable garden trails and practice quiz prompts for content creators.</p><a class="button button-play" href="{{ route('game-presets.create') }}">Create a preset →</a>
@forelse($presets as $preset)<p><a class="quiet-link" href="{{ route('game-presets.edit', $preset) }}">{{ $preset->name }}</a> · {{ $preset->status }} · v{{ $preset->currentRevision->number }}</p>@empty<p>No workshop presets yet. Built-in creator trails remain available.</p>@endforelse{{ $presets->links() }}</section>
@endsection
