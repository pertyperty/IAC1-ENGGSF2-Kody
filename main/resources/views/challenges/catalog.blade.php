@extends('layouts.learning')
@section('title', 'Coding challenges — Kody')
@section('content')
<section class="module-section page-width">
<x-catalog-header title="Coding challenges" description="Practice programming by solving a problem in your chosen language."/>
<form method="get" class="catalog-search catalog-filter-panel" action="{{ route('challenges.catalog') }}">
    <div class="filter-search"><label for="challenge-search">Search challenges</label<input id="challenge-search" name="q" type="search" maxlength="80" value="{{ $query }}" placeholder="Search a title or programming idea"></div>
    <details class="challenge-filters" @if(array_filter($filters)) open @endif>
        <summary>More filters</summary>
        <div class="filter-grid">
            <div><label for="challenge-language">Language</label><select id="challenge-language" name="language"><option value="">All languages</option>@foreach(config('challenges.languages') as $key => $label)<option value="{{ $key }}" @selected(($filters['language'] ?? null) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div><label for="challenge-difficulty">Difficulty</label><select id="challenge-difficulty" name="difficulty"><option value="">All difficulties</option>@foreach(['Easy','Medium','Hard'] as $difficulty)<option @selected(($filters['difficulty'] ?? null) === $difficulty)>{{ $difficulty }}</option>@endforeach</select></div>
            <div><label for="challenge-category">Topic</label><select id="challenge-category" name="category"><option value="">All topics</option>@foreach(config('challenges.categories') as $key => $label)<option value="{{ $key }}" @selected(($filters['category'] ?? null) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div><label for="challenge-tag">Concept</label><select id="challenge-tag" name="tag"><option value="">All concepts</option>@foreach(config('challenges.tags') as $key => $label)<option value="{{ $key }}" @selected(($filters['tag'] ?? null) === $key)>{{ $label }}</option>@endforeach</select></div>
        </div>
    </details>
    <div class="filter-actions"><button class="button button-dark button-small" type="submit">Search</button>@if($query !== '' || array_filter($filters))<a class="quiet-link" href="{{ route('challenges.catalog') }}">Clear filters</a>@endif<span class="filter-summary">{{ $challenges->total() }} {{ $challenges->total() === 1 ? 'challenge' : 'challenges' }} found</span></div>
</form>
<div class="catalog-results">
    @forelse($challenges as $revision)
        <x-catalog-entry :title="$revision->title" :url="route('challenges.show', $revision->challenge_id)" :action="auth()->check() ? 'View challenge' : 'Sign in to explore'" icon="code">
            <x-slot:meta><span>{{ config('challenges.languages.'.$revision->language) }}</span><span>{{ $revision->difficulty }}</span><span>{{ config('challenges.categories.'.$revision->category) }}</span></x-slot:meta>
            @if($revision->tags)<p>{{ collect($revision->tags)->map(fn ($tag) => config('challenges.tags.'.$tag))->implode(', ') }}</p>@endif
            <x-creator-byline :creator="$revision->challenge->creator"/>
            <p class="content-price">{{ $revision->challenge->creator?->account_status === \App\Enums\AccountStatus::Deleted ? 'Free · retained creator content' : ($revision->price_kb ? $revision->price_kb.' KB' : 'Free') }}@if($revision->minimum_xp > 0) · {{ $revision->minimum_xp }} XP to unlock @endif</p>
        </x-catalog-entry>
    @empty
        <x-empty-state title="No coding challenges found" description="Try different filters or practice a concept in a module." :action-url="route('learning.catalog')" action-label="Browse modules" />
    @endforelse
</div>
{{ $challenges->links() }}
</section>
@endsection
