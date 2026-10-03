@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('learning.catalog') }}">← More adventures</a><p class="overline">CREATOR ADVENTURE · {{ $revision->type }}</p><h1>{{ $revision->title }}</h1>
    <p>Explore at your own pace. A game or quiz win keeps your daily streak going.</p>@include('content.lesson')
</section>
@endsection
