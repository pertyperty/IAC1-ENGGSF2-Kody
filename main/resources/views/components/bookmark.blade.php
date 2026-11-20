@props(['kind', 'content'])
@can('viewLearning', \App\Models\LearningCourse::class)
@php($bookmarked = \Illuminate\Support\Facades\DB::table('learning_bookmarks')->where('user_id', auth()->id())->where('kind', $kind)->where('content_id', $content)->exists())
<form method="post" action="{{ route('bookmarks.update', [$kind, $content]) }}" class="bookmark-action">@csrf<input type="hidden" name="action" value="{{ $bookmarked ? 'remove' : 'add' }}"><button class="button button-secondary button-small" aria-pressed="{{ $bookmarked ? 'true' : 'false' }}">{{ $bookmarked ? 'Bookmarked' : 'Bookmark' }}</button></form>
@endcan
