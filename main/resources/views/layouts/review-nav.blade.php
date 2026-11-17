<nav class="studio-wayfinding" aria-label="Publication queues">
    <a href="{{ route('review-inbox') }}"><x-action-icon name="shield"/> Review inbox</a>
    <a href="{{ route('module-reviews.index') }}" @if(request()->routeIs('module-reviews.*')) aria-current="page" @endif><x-action-icon name="spark"/> Lesson reviews</a>
    <a href="{{ route('course-reviews.index') }}" @if(request()->routeIs('course-reviews.*')) aria-current="page" @endif><x-action-icon name="book"/> Course reviews</a>
    <a href="{{ route('challenge-reviews.index') }}" @if(request()->routeIs('challenge-reviews.*')) aria-current="page" @endif><x-action-icon name="code"/> Coding challenge reviews</a>
</nav>
