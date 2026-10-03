@extends('layouts.learning')
@section('title', 'Help workshop — Kody')
@section('content')
<section class="review-page page-width"><h1>Make the next step clearer.</h1><p>Publish useful answers for everyone, including guests.</p>@if(session('status'))<p class="lesson-note" role="status">{{ session('status') }}</p>@endif<a class="button button-play" href="{{ route('faq-management.create') }}">Write an answer →</a>
@forelse($entries as $entry)<p><a class="quiet-link" href="{{ route('faq-management.edit', $entry) }}">{{ $entry->question }}</a> · {{ config('help.categories')[$entry->category] }}</p>@empty<p>No published FAQ entries yet.</p>@endforelse{{ $entries->links() }}</section>
@endsection
