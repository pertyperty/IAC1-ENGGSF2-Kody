@extends('layouts.learning')
@section('title', 'Review inbox - Kody')
@section('content')
<section class="review-page page-width" aria-labelledby="review-inbox-heading">
    <h1 id="review-inbox-heading">Review inbox</h1>
    <p>Pending content and role applications, oldest first. Open an item to review it.</p>
    <form method="get" action="{{ route('review-inbox') }}" class="review-inbox-filter">
        <label for="review-category">Review type</label>
        <select id="review-category" name="category">
            <option value="all" @selected($category === 'all')>All pending</option>
            @foreach($categories as $key => $details)
            @can('viewAny', $details['model'])<option value="{{ $key }}" @selected($category === $key)>{{ $details['label'] }}</option>@endcan
            @endforeach
        </select>
        <button class="button button-secondary" type="submit">Filter</button>
    </form>
    <p role="status">{{ $items->total() }} pending {{ $items->total() === 1 ? 'item' : 'items' }}</p>
    <div class="review-list">
        @forelse($items as $item)
        <a href="{{ route($categories[$item->kind]['route'], $item->target_id) }}">
            <b>{{ $item->title }}</b>
            <span>{{ $categories[$item->kind]['label'] }} @if($item->number) &middot; Revision {{ $item->number }} &middot; {{ $item->username }} @endif</span>
        </a>
        @empty
        <x-empty-state title="Nothing pending here." description="Choose another review type or return when a creator submits an item."/>
        @endforelse
    </div>
    {{ $items->links() }}
    <details class="review-inbox-history"><summary>Review history and specialized queues</summary>
        <nav class="studio-wayfinding" aria-label="Specialized reviews">
            @foreach($categories as $details)
            @can('viewAny', $details['model'])<a href="{{ route($details['index']) }}">{{ $details['label'] }}</a>@endcan
            @endforeach
        </nav>
    </details>
</section>
@endsection
