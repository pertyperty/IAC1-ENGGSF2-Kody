@extends('layouts.learning')
@section('title', $attachment['name'].' — Kody')
@section('content')
<section class="review-page page-width"><div class="section-heading"><div><p class="overline">LESSON RESOURCE · {{ strtoupper($attachment['extension']) }}</p><h1>{{ $attachment['name'] }}</h1></div><a class="button button-play" href="{{ $url }}{{ str_contains($url, '?') ? '&' : '?' }}download=1">Download original ↓</a></div>
@if($attachment['extension'] === 'pdf')<iframe class="document-frame" title="{{ $attachment['name'] }} PDF preview" src="{{ $url }}{{ str_contains($url, '?') ? '&' : '?' }}inline=1"></iframe><p class="field-hint">If your browser cannot display this PDF, use Download original.</p>
@else<p class="field-hint">{{ $attachment['extension'] === 'pptx' ? 'Slide text preview' : 'Document text preview' }}. Download the original for its full formatting, images and diagrams.</p><div class="document-pages">@foreach($attachment['pages'] as $page)<article class="document-page">@if($attachment['extension'] === 'pptx')<span class="step-pill">Slide {{ $loop->iteration }}</span>@endif @forelse($page as $paragraph)<p>{{ $paragraph }}</p>@empty<p>This page contains images or diagrams. Download the original to view them.</p>@endforelse</article>@endforeach</div>@endif
</section>
@endsection
