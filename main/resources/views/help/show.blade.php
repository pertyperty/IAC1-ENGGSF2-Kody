@extends('layouts.learning')
@section('title', 'Your answer — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('help.index', ['category' => $entry->category]) }}">← {{ config('help.categories')[$entry->category] }}</a><h1>{{ $entry->question }}</h1><p class="lesson-note faq-answer">{{ $entry->answer }}</p><a class="button button-play" href="{{ route('home') }}">Back to play →</a></section>
@endsection
