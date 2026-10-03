@extends('layouts.learning')
@section('title', 'A little help — Kody')
@section('content')
<section class="review-page page-width"><span class="step-pill">YOUR NEXT SMALL STEP</span><h1>A little help goes a long way.</h1><p>Find an answer, then get back to your adventure.</p>
<form class="studio-form" method="GET" action="{{ route('help.index') }}"><label for="q">What are you curious about?</label><input id="q" name="q" maxlength="80" value="{{ $filters['q'] ?? '' }}" placeholder="Search questions and answers">
<label for="category">Explore a topic</label><select id="category" name="category"><option value="">All topics</option>@foreach(config('help.categories') as $key => $label)<option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $label }}</option>@endforeach</select><button class="button button-play">Find answers →</button></form>
@forelse($entries as $entry)<article class="lesson-note"><span class="step-pill">{{ config('help.categories')[$entry->category] }}</span><h2><a class="quiet-link" href="{{ route('help.show', $entry) }}">{{ $entry->question }}</a></h2><details><summary>Show the answer</summary><p class="faq-answer">{{ $entry->answer }}</p></details></article>@empty<p class="lesson-note">No answers here yet. Try another topic or search.</p>@endforelse{{ $entries->links() }}
</section>
@endsection
