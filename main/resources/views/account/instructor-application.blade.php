@extends('layouts.account')
@section('title', 'Become a creator — Kody')
@section('content')
<p class="eyebrow">TURN KNOWLEDGE INTO ADVENTURES</p><h1>Become a learning creator</h1>
<p class="intro">Share your teaching credentials for review. Keep learning while your application is checked.</p>
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($application)<p class="notice">Application: {{ $application->verification_status }}</p>@endif
@if($pendingContributor)<p class="notice">Your Contributor application is awaiting review. Wait for its decision before applying for Instructor access.</p>
@elseif(!$application || $application->verification_status === 'Rejected')
<form class="account-form" method="POST" enctype="multipart/form-data" action="{{ route('instructor-application.store') }}">@csrf
<input type="hidden" name="record_version" value="{{ $application?->record_version ?? 0 }}">
<label>Institution<input name="institution_name" value="{{ old('institution_name') }}" maxlength="100" required></label>
<label>Specialization<input name="specialization" value="{{ old('specialization') }}" maxlength="100" required></label>
<label>Teaching credentials<input type="file" name="credential_document" accept=".pdf,.jpg,.jpeg,.png" required><small>PDF, PNG or JPEG; up to {{ number_format(config('account.credentials.max_kilobytes') / 1024, 1) }} MB. Stored privately.</small></label>
<label><input type="checkbox" name="confirmed" value="1" required> I confirm these credentials are ready for review.</label>
<button class="primary-button">{{ $application ? 'Submit a new application version' : 'Apply to become a creator' }}</button></form>
@else<p>Your application is awaiting review. Another submission is available only after rejection.</p>@endif
<h2>Your application history</h2>@forelse($history as $version)<p>Version {{ $version->application_version }} · {{ $version->verification_status }}@if($version->verification_notes)<br>{{ $version->verification_notes }}@endif</p>@empty<p>Your first creator chapter starts here.</p>@endforelse
@if($history instanceof \Illuminate\Contracts\Pagination\Paginator){{ $history->links() }}@endif
<p class="secondary-link"><a href="{{ route('account.show') }}">Back to your profile</a></p>
@endsection
