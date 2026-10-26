@extends('layouts.learning')
@section('title', 'Review later — Kody')
@section('content')
<section class="review-page page-width"><h1>Review later</h1><p>Revisit a concept when you need more practice.</p>
<ul class="review-list">@forelse($items as $item)@php($step = $levels->get($item->level_id))<li><div>
@if($step && $step['unlocked'])<a href="{{ route('tower.show', $item->level_id) }}">Level {{ $step['level']->position }} · {{ $step['level']->currentRevision->title }}</a>
@if($item->revision_id !== $step['level']->current_revision_id)<p class="field-hint">Updated since you saved it. Opens the current lesson.</p>@endif
@else<span>This lesson is currently unavailable.</span>@endif
</div><form method="post" action="{{ route('review-queue.update', $item->level_id) }}">@csrf<input type="hidden" name="action" value="remove"><button class="button button-secondary">Remove</button></form></li>
@empty<li>No lessons saved. Choose “Review later” inside a Tower lesson.</li>@endforelse</ul>
<a href="{{ route('home') }}">Back to Tower</a></section>
@endsection
