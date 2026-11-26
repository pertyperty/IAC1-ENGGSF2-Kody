<nav class="studio-wayfinding" aria-label="Publication queues">
    <a href="{{ route('review-inbox') }}" @if(request()->routeIs('review-inbox')) aria-current="page" @endif><x-action-icon name="shield"/> Review inbox</a>
    <a href="{{ route('module-reviews.index') }}" @if(request()->routeIs('module-reviews.*')) aria-current="page" @endif><x-action-icon name="module"/> Module reviews</a>
    <a href="{{ route('course-reviews.index') }}" @if(request()->routeIs('course-reviews.*')) aria-current="page" @endif><x-action-icon name="course"/> Course reviews</a>
    <a href="{{ route('challenge-reviews.index') }}" @if(request()->routeIs('challenge-reviews.*')) aria-current="page" @endif><x-action-icon name="code"/> Coding challenge reviews</a>
    @can('viewAny', \App\Models\InstructorApplication::class)<a href="{{ route('instructor-reviews.index') }}" @if(request()->routeIs('instructor-reviews.*')) aria-current="page" @endif><x-action-icon name="create"/> Instructor applications</a>@endcan
    @can('viewAny', \App\Models\ContributorApplication::class)<a href="{{ route('contributor-reviews.index') }}" @if(request()->routeIs('contributor-reviews.*')) aria-current="page" @endif><x-action-icon name="plus"/> Contributor applications</a>@endcan
</nav>
