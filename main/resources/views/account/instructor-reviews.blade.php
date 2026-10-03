@extends('layouts.learning')
@section('title', 'Instructor reviews — Kody')
@section('content')
<section class="review-page page-width"><p class="overline">HELP GREAT TEACHERS BECOME CREATORS</p><h1>Instructor applications</h1><p>Credentials are private. Review their credibility before granting creator privileges.</p><div class="review-list">@forelse($applications as $application)<a href="{{ route('instructor-reviews.show', $application) }}"><b>{{ $application->user->name }}</b><span>{{ $application->institution_name }}</span><span>{{ $application->verification_status }} →</span></a>@empty<p>No applications to review.</p>@endforelse</div>{{ $applications->links() }}<a class="quiet-link" href="{{ route('account.show') }}">← Your profile</a></section>
@endsection
