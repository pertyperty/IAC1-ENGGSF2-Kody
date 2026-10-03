@extends('layouts.learning')
@section('title', 'Your updates — Kody')
@section('content')
<section class="review-page page-width"><h1>A little news for you.</h1><p>Your adventure reviews arrive here.</p>
    @forelse($notifications as $notification)<div class="lesson-note"><p><b>{{ $notification->data['title'] }}</b> · {{ $notification->data['decision'] }}</p><p><a class="quiet-link" href="{{ $notification->type === 'course.reviewed' ? route('courses.edit', $notification->data['course_id']) : route('studio.edit', $notification->data['module_id']) }}">View adventure and feedback →</a></p>@if($notification->read_at)<p class="field-hint">Read</p>@else<form method="post" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="button button-dark button-small">Mark as read</button></form>@endif</div>@empty<p class="lesson-note">All quiet for now. Keep making little discoveries.</p>@endforelse
    {{ $notifications->links() }}
</section>
@endsection
