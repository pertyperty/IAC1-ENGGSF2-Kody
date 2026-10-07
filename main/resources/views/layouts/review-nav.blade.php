<nav class="studio-wayfinding" aria-label="Publication queues">
    <a href="{{ route('module-reviews.index') }}" @if(request()->routeIs('module-reviews.*')) aria-current="page" @endif>✦ Adventure reviews</a>
    <a href="{{ route('course-reviews.index') }}" @if(request()->routeIs('course-reviews.*')) aria-current="page" @endif>≡ Course reviews</a>
    <a href="{{ route('challenge-reviews.index') }}" @if(request()->routeIs('challenge-reviews.*')) aria-current="page" @endif>{ } Coding quest reviews</a>
</nav>
