@extends('layouts.learning')
@section('title', 'Notifications - Kody')
@section('content')
<section class="review-page page-width notification-page">
    <h1>Notifications</h1><p>{{ $unreadCount }} unread. Account notices, review decisions and payment updates.</p>
    <form method="get" class="catalog-search" action="{{ route('notifications.index') }}">
        <label for="notification-state">Show</label><select id="notification-state" name="state">@foreach(['all' => 'All notifications', 'unread' => 'Unread', 'read' => 'Read'] as $value => $label)<option value="{{ $value }}" @selected($state === $value)>{{ $label }}</option>@endforeach</select>
        <button class="button button-dark">Apply filter</button>
    </form>
    <ol class="notification-list">
    @forelse($notifications as $notification)
        @php($feedbackUrl = $feedbackLinks[$notification->id] ?? null)
        <li class="notification-item {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
            <div><span class="status-pill">{{ $notification->read_at ? 'Read' : 'Unread' }}</span><h2>{{ $notification->data['title'] ?? 'Account update' }}</h2>
            @if(isset($notification->data['decision']))<p>{{ $notification->data['decision'] }}</p>@endif
            <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->setTimezone('Asia/Manila')->format('M j, Y - g:i A') }} Manila</time>
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
