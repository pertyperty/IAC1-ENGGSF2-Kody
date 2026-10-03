@extends('layouts.account')
@section('title', 'Become a Contributor — Kody')
@section('content')
<p class="eyebrow">BUILD THE NEXT QUEST</p><h1>Become a Contributor</h1>
<p class="intro">Share what you know by creating coding challenges for fellow explorers. Your Learner access stays available while we review your application.</p>
@if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<p>Account age: {{ $progress['account_age_days'] }} / 30 days · Modules completed: {{ $progress['completed_modules_count'] }} / 25 · Coding challenges passed: {{ $progress['completed_challenges_count'] }} / 50</p>
<p>Each module and coding challenge counts once. Landing-page practice does not count toward this application.</p>
@if($pendingInstructor)<p class="notice">Your Instructor application is awaiting review. Finish that review before starting another role application.</p>@endif
@if($canApply)
<form class="account-form" method="POST" action="{{ route('contributor-application.store') }}" enctype="multipart/form-data">@csrf
<input type="hidden" name="previous_application_id" value="{{ $latest?->id ?? 0 }}">
<label>Why would you like to contribute?<textarea name="request_message" maxlength="500" required>{{ old('request_message') }}</textarea></label>
<label>Portfolio link (optional)<input type="url" name="portfolio_link" maxlength="255" value="{{ old('portfolio_link') }}"></label>
<label>Supporting credentials<input type="file" name="credential_document" accept=".pdf,.jpg,.jpeg,.png" required></label><p>PDF, JPEG or PNG, up to {{ config('account.credentials.max_kilobytes') }} KB. Only authorized reviewers can download your private document.</p>
<label><input type="checkbox" name="confirmed" value="1" required> I confirm these application details.</label><button class="primary-button">Submit for review</button></form>
@elseif(!$progress['eligible'] && !$latest)<p class="notice">Keep exploring. All three milestones are required before applying.</p>@endif
@foreach($history as $application)<section class="notice"><p>Application #{{ $application->id }} · {{ $application->approval_status }}</p><p>{{ $application->request_message }}</p>@if($application->moderator_feedback)<p>Reviewer feedback: {{ $application->moderator_feedback }}</p>@endif</section>@endforeach
{{ $history->links() }}<p class="secondary-link"><a href="{{ route('account.show') }}">Back to your account</a></p>
@endsection
