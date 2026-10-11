@extends('layouts.learning')
@section('title', 'A little help — Kody')
@section('content')
<section class="review-page page-width help-page"><div class="help-heading"><x-kody-art size="104"/><div><span class="step-pill">YOUR NEXT SMALL STEP</span><h1>A little help goes a long way.</h1><p>Find an answer, then get back to your adventure.</p></div></div>
<div class="help-layout"><aside class="help-search"><h2>Find your answer</h2>
<form class="studio-form" method="GET" action="{{ route('help.index') }}"><label for="q">What are you curious about?</label><input id="q" name="q" maxlength="80" value="{{ $filters['q'] ?? '' }}" placeholder="Search questions and answers">
<label for="category">Explore a topic</label><select id="category" name="category"><option value="">All topics</option>@foreach(config('help.categories') as $key => $label)<option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $label }}</option>@endforeach</select><button class="button button-play">Find answers →</button></form>
<nav class="help-topics" aria-label="Help topics">@foreach(config('help.categories') as $key => $label)<a href="{{ route('help.index', ['category' => $key, 'q' => $filters['q'] ?? '']) }}" @if(($filters['category'] ?? '') === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav><button type="button" class="button button-secondary" data-tour-open>Take a guided tour</button></aside>
<div>@forelse($entries->getCollection()->groupBy('category') as $category => $group)<section class="help-group"><h2>{{ config('help.categories')[$category] }}</h2><div class="help-grid">@foreach($group as $entry)<article class="lesson-note"><h3><a class="quiet-link" href="{{ route('help.show', $entry) }}">{{ $entry->question }}</a></h3><details><summary>Show the answer</summary><p class="faq-answer">{{ $entry->answer }}</p></details></article>@endforeach</div></section>@empty<x-empty-state title="No answers here yet" description="Try another topic or search." :action-url="route('help.index')" action-label="Reset search"/>@endforelse{{ $entries->links() }}</div></div>
</section>
@endsection
