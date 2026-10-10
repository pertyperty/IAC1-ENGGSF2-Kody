@extends('layouts.learning')
@section('title', 'Your updates — Kody')
@section('content')
<section class="review-page page-width"><h1>A little news for you.</h1><p>Reviews, account notices and wallet updates, together in one place.</p>
    @forelse($notifications as $notification)
    @php $feedbackUrl = $feedbackLinks[$notification->id] ?? null; @endphp
    <div class="lesson-note"><p><b>{{ $notification->data['title'] ?? 'Account update' }}</b> · {{ $notification->data['decision'] ?? 'Recorded in your history' }}</p>@if($notification->type === 'content.moderated')<p>This staff action was recorded at {{ $notification->created_at->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila. Later decisions may change the current block. Restoration follows the content’s existing lifecycle.</p>@endif @if($feedbackUrl)<p><a class="quiet-link" href="{{ $feedbackUrl }}">Open current details →</a></p>@endif @if($notification->read_at)<p class="field-hint">Read</p>@else<form method="post" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="button button-dark button-small">Mark as read</button></form>@endif</div>@empty<p class="lesson-note">All quiet for now. Keep making little discoveries.</p>@endforelse
    {{ $notifications->links() }}
</section>
@endsection
