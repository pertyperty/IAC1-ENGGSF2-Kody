@extends('layouts.learning')
@section('title', 'Game presets - Kody')
@section('content')
<section class="review-page page-width"><h1>Game presets</h1><p>Browse saved activities, edit their defaults and check which lessons use them.</p>
<form method="get" class="catalog-search catalog-filter-panel" action="{{ route('game-presets.index') }}">
<div class="filter-grid"><label>Preset name<input type="search" name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="Search saved presets"></label>
<label>Status<select name="status"><option value="">All statuses</option>@foreach(['Active', 'Inactive'] as $status)<option @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></label></div>
<div class="filter-actions"><button class="button button-dark">Apply filters</button>@if(array_filter($filters))<a class="quiet-link" href="{{ route('game-presets.index') }}">Clear filters</a>@endif</div></form>
<ul class="preset-browser">@forelse($presets as $preset)<li><div><h2><a class="quiet-link" href="{{ route('game-presets.edit', $preset) }}">{{ $preset->name }}</a></h2><p>{{ $preset->status }} | Version {{ $preset->currentRevision->number }} | {{ $preset->lesson_revisions_count }} saved lesson revisions</p></div><a class="button button-secondary" href="{{ route('game-presets.edit', $preset) }}">View preset and usage</a></li>@empty<li class="empty-state"><h2>No matching presets</h2><p>Try another name or clear the filters. Built-in activities remain available to lesson creators.</p></li>@endforelse</ul>
{{ $presets->links() }}</section>
@endsection
