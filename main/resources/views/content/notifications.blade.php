@extends('layouts.learning')
@section('title', 'Notifications - Kody')
@section('content')
<section class="review-page page-width notification-page">
    <x-catalog-header title="Notifications" :description="trans_choice(':count unread notification|:count unread notifications', $unreadCount)"/>
    <form method="get" class="catalog-search catalog-filter-panel" action="{{ route('notifications.index') }}">
        <label for="notification-state">Show</label><select id="notification-state" name="state">@foreach(['all' => 'All notifications', 'unread' => 'Unread', 'read' => 'Read'] as $value => $label)<option value="{{ $value }}" @selected($state === $value)>{{ $label }}</option>@endforeach</select>
        <button class="button button-dark">Apply filter</button>
    </form>
    <ol class="notification-list">
    @forelse($notifications as $notification)
        @php($feedbackUrl = $feedbackLinks[$notification->id] ?? null)
        <li class="notification-item {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
            <div>
            <h2>{{ $notification->data['title'] ?? 'Account update' }}</h2>
            <div class="notification-meta">
                @unless($notification->read_at)<span class="status-pill">Unread</span>@endunless
                <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->setTimezone('Asia/Manila')->format('M j, Y · g:i A') }} Manila</time>
            </div>
            @if(isset($notification->data['decision']))<p>{{ $notification->data['decision'] }}</p>@endif
            @if($feedbackUrl)<p><a class="quiet-link" href="{{ $feedbackUrl }}">View details</a></p>@endif</div>
            @unless($notification->read_at)
                <form method="post" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <input type="hidden" name="state" value="{{ $state }}">
                    <input type="hidden" name="page" value="{{ $notifications->currentPage() }}">
                    <button class="button button-secondary button-small">Mark as read</button>
                </form>
            @endunless
        </li>
    @empty<li class="empty-state"><h2>{{ $state === 'unread' ? 'All caught up' : 'No notifications here' }}</h2><p>New account and learning updates will appear here.</p></li>@endforelse
    </ol>
    {{ $notifications->links() }}
</section>
@endsection
