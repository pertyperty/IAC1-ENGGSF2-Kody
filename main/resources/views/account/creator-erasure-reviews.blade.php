@extends('layouts.learning')
@section('title', 'Creator privacy reviews — Kody')
@section('content')
<section class="review-page page-width"><h1>Creator erasure reviews</h1><p>Inspect every saved version, including hidden tests and earlier drafts. Content remains with a neutral creator identity and becomes free for new learners.</p><div class="review-list">@forelse($reviews as $review)<a href="{{ route('creator-erasure.show', $review->id) }}"><b>Creator account #{{ $review->user_id }}</b><span>{{ $review->created_at }} · Privacy inventory →</span></a>@empty<p>No pending creator erasure reviews.</p>@endforelse</div>{{ $reviews->links() }}</section>
@endsection
