@extends('layouts.learning')
@section('title', 'Instructor reviews — Kody')
@section('content')
<section class="review-page page-width"><p class="overline">HELP GREAT TEACHERS BECOME CREATORS</p><h1>Instructor applications</h1><p>Credentials are private. Review their credibility before granting creator privileges.</p><div class="review-list">@forelse($applications as $application)<a href="{{ route('instructor-reviews.show', $application) }}"><b>{{ $application->user->name }}</b><span>{{ $application->institution_name }}</span><span>{{ $application->verification_status }} <x-action-icon name="right"/></span></a>@empty<x-empty-state title="No applications to review." description="New Instructor applications will appear here for credential review."/>@endforelse</div>{{ $applications->links() }}<a class="quiet-link" href="{{ route('account.show') }}"><x-action-icon name="left"/> Your profile</a></section>
@endsection
