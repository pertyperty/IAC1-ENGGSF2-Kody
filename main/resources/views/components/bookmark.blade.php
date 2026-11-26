@props(['kind', 'content', 'bookmarked' => null, 'compact' => false, 'label' => null])
@can('viewLearning', \App\Models\LearningCourse::class)
@php
    $isBookmarked = is_bool($bookmarked)
        ? $bookmarked
        : \Illuminate\Support\Facades\DB::table('learning_bookmarks')
            ->where('user_id', auth()->id())
            ->where('kind', $kind)
            ->where('content_id', $content)
            ->exists();
@endphp
@if($compact)
<form method="post" action="{{ route('bookmarks.update', [$kind, $content]) }}" class="bookmark-action bookmark-action--compact">
    @csrf
    <input type="hidden" name="action" value="{{ $isBookmarked ? 'remove' : 'add' }}">
    <button type="submit" class="bookmark-card-toggle {{ $isBookmarked ? 'is-bookmarked' : '' }}" aria-pressed="{{ $isBookmarked ? 'true' : 'false' }}" aria-label="{{ $isBookmarked ? 'Remove bookmark: '.($label ?? $kind) : 'Bookmark: '.($label ?? $kind) }}" title="{{ $isBookmarked ? 'Bookmarked — click to remove' : 'Save to bookmarks' }}">
        <x-action-icon name="bookmark" class="bookmark-icon"/>
        @if($isBookmarked)<span class="bookmark-card-state" aria-hidden="true">Saved</span>@endif
    </button>
</form>
@else
<div class="bookmark-control">
    <form method="post" action="{{ route('bookmarks.update', [$kind, $content]) }}" class="bookmark-action">
        @csrf
        <input type="hidden" name="action" value="{{ $isBookmarked ? 'remove' : 'add' }}">
        <button type="submit" class="button button-secondary button-small bookmark-toggle {{ $isBookmarked ? 'is-bookmarked' : '' }}" aria-pressed="{{ $isBookmarked ? 'true' : 'false' }}" title="{{ $isBookmarked ? 'Click to remove from bookmarks' : 'Save to your bookmarks' }}">
            <x-action-icon name="bookmark" class="bookmark-icon"/><span>{{ $isBookmarked ? 'Bookmarked' : 'Bookmark' }}</span>
        </button>
    </form>
    @if($isBookmarked)
        <a class="bookmark-saved-link" href="{{ route('course-learning.mine', ['type' => 'bookmarks']) }}">Saved in Library</a>
    @endif
</div>
@endif
@endcan
