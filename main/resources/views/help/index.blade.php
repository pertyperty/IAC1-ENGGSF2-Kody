@extends('layouts.learning')
@section('title', 'Help - Kody')
@section('content')
<section class="review-page page-width help-page">
<div class="section-heading"><div><h1>{{ $manage ? 'Help and FAQs' : 'How can we help?' }}</h1><p>{{ $manage ? 'Read, edit and preview the answers your learners see.' : 'Search questions or choose a topic.' }}</p></div>@if($manage)<a class="button button-play" href="{{ route('help.index', ['new' => 1]) }}">Add FAQ</a>@endif</div>
<form class="catalog-search catalog-filter-panel" method="get" action="{{ route('help.index') }}"><div class="filter-grid"><label>Search help<input name="q" maxlength="80" value="{{ $filters['q'] ?? '' }}" placeholder="Search questions and answers"></label><div><label for="help-topic">Topic</label><select id="help-topic" name="category"><option value="">All topics</option>@foreach(config('help.categories') as $key => $label)<option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></div></div><div class="filter-actions"><button class="button button-dark">Search</button>@if(array_filter($filters))<a class="quiet-link" href="{{ route('help.index') }}">Clear filters</a>@endif<button type="button" class="button button-secondary" data-tour-open>Take a guided tour</button></div></form>
<div @if($editing) data-live-preview @endif class="help-reading-workspace {{ $editing ? 'is-editing' : '' }}">
<div>
@if($editing)<article class="faq-live-preview" aria-label="FAQ preview"><p class="overline">UNSAVED PREVIEW</p><h2 data-preview-value="question" data-preview-placeholder="Your question">{{ $editorEntry?->question }}</h2><p class="faq-answer" data-preview-value="answer" data-preview-placeholder="Your answer will appear here.">{{ $editorEntry?->answer }}</p></article><h2>Published FAQs</h2>@endif
@forelse($entries as $entry)
<article class="faq-result"><details @if($editorEntry?->id === $entry->id) open @endif><summary>{{ $entry->question }}</summary><p class="faq-answer">{{ $entry->answer }}</p><a class="quiet-link" href="{{ route('help.show', $entry) }}">Open answer</a></details>@if($manage)<a class="button button-secondary button-small" href="{{ route('help.index', ['edit' => $entry->id]) }}">Edit FAQ<span class="sr-only">: {{ $entry->question }}</span></a>@endif</article>
@empty<x-empty-state title="No answers found" description="Try another topic or clear your search." :action-url="route('help.index')" action-label="Clear search"/>@endforelse
{{ $entries->links() }}
</div>
@if($editing)<aside class="faq-editor"><div class="section-heading"><h2>{{ $editorEntry ? 'Edit FAQ' : 'Add FAQ' }}</h2><a class="quiet-link" href="{{ route('help.index') }}">Close editor</a></div>@include('help.form')</aside>@endif
</div></section>
@endsection
