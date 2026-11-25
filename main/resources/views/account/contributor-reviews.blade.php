@extends('layouts.account')
@section('title', 'Contributor applications — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
<a class="quiet-link" href="{{ route('review-inbox') }}"><x-action-icon name="left"/> Review inbox</a>
<p class="overline">HELP ACTIVE LEARNERS BECOME CREATORS</p>
<h1>Review future quest creators</h1><p>Check the applicant’s supporting work before making a decision.</p>
<div class="review-list">@forelse($applications as $application)<a href="{{ route('contributor-reviews.show', $application) }}"><b>{{ $application->user->username }}</b><span>{{ $application->approval_status }}</span></a>@empty<x-empty-state title="No Contributor applications yet." description="New Contributor applications will appear here for credential review."/>@endforelse</div>
{{ $applications->links() }}
@endsection
