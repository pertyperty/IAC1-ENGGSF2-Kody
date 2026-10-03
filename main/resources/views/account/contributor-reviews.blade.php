@extends('layouts.account')
@section('title', 'Contributor applications — Kody')
@section('content')
<h1>Review future quest creators</h1><p>Check the applicant’s supporting work before making a decision.</p>
@forelse($applications as $application)<p><a href="{{ route('contributor-reviews.show', $application) }}">{{ $application->user->name }} · {{ $application->approval_status }}</a></p>@empty<p>No Contributor applications yet.</p>@endforelse
{{ $applications->links() }}
@endsection
