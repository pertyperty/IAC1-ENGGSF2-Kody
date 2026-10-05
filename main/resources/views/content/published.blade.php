@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('learning.catalog') }}">← More adventures</a><p class="overline">CREATOR ADVENTURE · {{ $revision->type }}</p><h1>{{ $revision->title }}</h1>
    @if($participant)<p>Explore at your own pace. A game or quiz win keeps your daily streak going. Your first validated assessment completion earns 40 XP.</p>@else<p>Staff preview. Practice here stays local and earns no progress or rewards.</p>@endif
    @include('content.lesson', ['headingShown' => true, 'preview' => !$participant])
@include('engagement.reactions')
</section>
@endsection
