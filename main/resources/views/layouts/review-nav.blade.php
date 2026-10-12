<nav class="studio-wayfinding" aria-label="Publication queues">
    <a href="{{ route('module-reviews.index') }}" @if(request()->routeIs('module-reviews.*')) aria-current="page" @endif><x-action-icon name="spark"/> Adventure reviews</a>
    <a href="{{ route('course-reviews.index') }}" @if(request()->routeIs('course-reviews.*')) aria-current="page" @endif><x-action-icon name="book"/> Course reviews</a>
    <a href="{{ route('challenge-reviews.index') }}" @if(request()->routeIs('challenge-reviews.*')) aria-current="page" @endif><x-action-icon name="code"/> Coding quest reviews</a>
</nav>
