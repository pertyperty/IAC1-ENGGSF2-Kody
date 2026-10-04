@extends('layouts.learning')
@section('title', 'Review an adventure — Kody')
@section('content')
<section class="review-page page-width">
@include('transactions.review-access')
<a class="quiet-link" href="{{ route('module-reviews.index') }}">← Review queue</a><h1>Give this adventure a look.</h1><p>Revision {{ $revision->number }} · {{ $revision->review_status }}</p>
    @if(session('status'))<p class="lesson-note" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="studio-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @include('content.lesson', ['module' => null, 'preview' => true])
    @if($revision->review_status === 'Pending' && in_array($module->status, ['Draft', 'Published'], true))<form method="post" action="{{ route('module-reviews.review', $module) }}" class="studio-form">@csrf<input type="hidden" name="record_version" value="{{ $module->record_version }}"><label for="review_notes">Feedback (required for rejection)</label><textarea id="review_notes" name="review_notes" maxlength="255" rows="3">{{ old('review_notes') }}</textarea><div class="studio-actions"><button class="button button-play" name="decision" value="Approved">Approve and publish</button><button class="button button-dark" name="decision" value="Rejected">Return with feedback</button></div></form>@elseif($revision->review_notes)<p class="lesson-note">{{ $revision->review_notes }}</p>@endif
</section>
@endsection
