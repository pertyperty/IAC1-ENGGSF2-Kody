@extends('layouts.account')
@section('title', 'Contributor applications — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
@include('layouts.review-nav')
<p class="overline">CONTRIBUTOR APPLICATIONS</p>
<h1>Contributor applications</h1><p>Check each applicant’s eligibility summary and private supporting work before making a decision.</p>
<div class="review-list">@forelse($applications as $application)<a href="{{ route('contributor-reviews.show', $application) }}"><b>{{ $application->user->username }}</b><span>{{ $application->approval_status }}</span></a>@empty<x-empty-state title="No Contributor applications yet." description="New Contributor applications will appear here for credential review."/>@endforelse</div>
{{ $applications->links() }}
@endsection
