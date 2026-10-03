@extends('layouts.learning')
@section('title', 'Staff content moderation — Kody')
@section('content')
<section class="review-page page-width"><h1>Keep learning safe.</h1><p>Review flagged content before confirming staff withdrawal. This block is separate from creator archival.</p>
<form method="GET" action="{{ route('content-moderation.index') }}" class="account-form"><label>Content<select name="kind">@foreach(['module' => 'Modules', 'course' => 'Courses', 'challenge' => 'Challenges'] as $value => $label)<option value="{{ $value }}" @selected($kind === $value)>{{ $label }}</option>@endforeach</select></label><button class="button button-dark">Browse content</button></form>
<div class="review-list">@forelse($items as $item)@can('moderate', $item)<a href="{{ route('content-moderation.show', [$kind, $item->id]) }}"><b>{{ $item->publishedRevision?->title ?? ucfirst($kind).' #'.$item->id }}</b><span>{{ $item->status }} · {{ $item->isWithdrawn() ? 'Staff withdrawal active' : 'No staff block' }}</span></a>@endcan @empty<p>No published or archived content.</p>@endforelse</div>{{ $items->links() }}</section>
@endsection
