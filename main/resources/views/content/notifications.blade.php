@extends('layouts.learning')
@section('title', 'Your updates — Kody')
@section('content')
<section class="review-page page-width"><h1>A little news for you.</h1><p>Your adventure reviews arrive here.</p>
    @forelse($notifications as $notification)
    @php $feedbackUrl = match($notification->type) { 'contributor.application' => ($notification->data['reviewer'] ?? false) ? route('contributor-reviews.show', $notification->data['application_id']) : route('contributor-application.create'), 'course.reviewed' => route('courses.edit', $notification->data['course_id']), 'challenge.reviewed' => route('challenges.edit', $notification->data['challenge_id']), 'module.reviewed' => route('studio.edit', $notification->data['module_id']), default => null }; @endphp
    <div class="lesson-note"><p><b>{{ $notification->data['title'] }}</b> · {{ $notification->data['decision'] }}</p>@if($notification->type === 'content.moderated')<p>This staff action was recorded at {{ $notification->created_at->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila. Later decisions may change the current block. Restoration follows the content’s existing lifecycle.</p>@endif @if($feedbackUrl)<p><a class="quiet-link" href="{{ $feedbackUrl }}">View details and feedback →</a></p>@endif @if($notification->read_at)<p class="field-hint">Read</p>@else<form method="post" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="button button-dark button-small">Mark as read</button></form>@endif</div>@empty<p class="lesson-note">All quiet for now. Keep making little discoveries.</p>@endforelse
    {{ $notifications->links() }}
</section>
@endsection
