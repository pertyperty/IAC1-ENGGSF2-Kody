@extends('layouts.learning')
@section('title', 'Creator studio — Kody')
@section('content')
<section class="review-page page-width">
    <p class="overline">MAKE SOMEONE’S NEXT AHA MOMENT</p><h1>Your creator studio.</h1>
    <p>Turn an idea into a little adventure. Add a lesson, choose a game or quiz, and send it for review.</p>
    <a class="button button-play" href="{{ route('studio.create') }}">Create an adventure +</a>
    <p><a class="quiet-link" href="{{ route('courses.index') }}">Build a course from your adventures →</a></p>
    <div class="review-list">@forelse($modules as $module)<a href="{{ route('studio.edit', $module) }}"><b>{{ $module->latestRevision->title }}</b><span>{{ $module->status }} · Revision {{ $module->latestRevision->number }} · {{ $module->latestRevision->review_status }}</span></a>@empty<p class="lesson-note">Your first adventure starts with one idea. What would you love to help someone understand?</p>@endforelse</div>
    {{ $modules->links() }}
</section>
@endsection
