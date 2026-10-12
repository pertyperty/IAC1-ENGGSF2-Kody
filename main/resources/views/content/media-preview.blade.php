@extends('layouts.learning')
@section('title', $attachment['name'].' — Kody')
@section('content')
<section class="review-page page-width"><div class="section-heading"><div><p class="overline">LESSON RESOURCE · {{ strtoupper($attachment['extension']) }}</p><h1>{{ $attachment['name'] }}</h1></div><a class="button button-play" href="{{ $url }}{{ str_contains($url, '?') ? '&' : '?' }}download=1" data-navigation-download>Download original <x-action-icon name="download"/></a></div>
<div class="document-stage" data-document-stage>
<div class="document-stage-tools"><button type="button" class="button button-secondary button-small" data-document-minimize><x-action-icon name="menu"/> Minimize navigation</button><button type="button" class="button button-play button-small" data-document-fullscreen aria-pressed="false"><x-action-icon name="expand"/> Fullscreen preview</button></div>
@include('content.media-body')
</div>
</section>
@endsection
