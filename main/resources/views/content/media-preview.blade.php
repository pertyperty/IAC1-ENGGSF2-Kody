@extends('layouts.learning')
@section('title', $attachment['name'].' — Kody')
@section('content')
<section class="review-page page-width"><div class="section-heading"><div><p class="overline">LESSON RESOURCE · {{ strtoupper($attachment['extension']) }}</p><h1>{{ $attachment['name'] }}</h1></div><a class="button button-play" href="{{ $url }}{{ str_contains($url, '?') ? '&' : '?' }}download=1" data-navigation-download>Download original <x-action-icon name="download"/></a></div>
@include('content.media-body')
</section>
@endsection
