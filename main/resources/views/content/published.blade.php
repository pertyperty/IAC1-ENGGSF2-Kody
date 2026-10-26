@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="review-page page-width lesson-page"><a class="quiet-link" href="{{ route('learning.catalog') }}"><x-action-icon name="left"/> More adventures</a><p class="overline">CREATOR ADVENTURE · {{ $revision->type }}</p><h1>{{ $revision->title }}</h1><x-creator-byline :creator="$revision->module->creator"/>
    @if($participant)<p>Explore at your own pace. A game or quiz win keeps your daily streak going. Your first validated assessment completion earns 40 XP.</p>@else<p>Staff preview. Practice here stays local and earns no progress or rewards.</p>@endif
    @include('content.lesson', ['headingShown' => true, 'preview' => !$participant])
    @if($participant && $revision->type === 'Article' && !$revision->assessment)
        @if($articleRead)<p class="notice"><x-action-icon name="check"/> Article completed. Your first completion reward is saved.</p>
        @else<form method="post" action="{{ route('modules.read', [$module, $revision->id]) }}">@csrf<input type="hidden" name="confirmed" value="1"><p class="field-hint">Finished reading? Your first completion of this module earns 40 XP, once across course and standalone access. Reading grants no streak or assessment eligibility credit.</p><button class="button button-play"><x-action-icon name="check"/> Mark as read</button></form>@endif
    @endif
@include('engagement.reactions')
</section>
@endsection
