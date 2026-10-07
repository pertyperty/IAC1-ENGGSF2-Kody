<nav class="workspace-nav" aria-label="{{ ($mobile ?? false) ? 'Mobile workspace' : 'Your workspace' }}">
    <p class="rail-label">Your playground</p>
    <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard', 'learning.show')) aria-current="page" @endif><span aria-hidden="true">◈</span>Overview</a>
    <a href="{{ route('learning.catalog') }}" @if(request()->routeIs('learning.catalog', 'modules.*', 'course-learning.show', 'course-learning.lesson')) aria-current="page" @endif><span aria-hidden="true">▤</span>Learning catalog</a>
    @can('viewLearning', \App\Models\LearningCourse::class)<a href="{{ route('course-learning.mine') }}" @if(request()->routeIs('course-learning.mine')) aria-current="page" @endif><span aria-hidden="true">↗</span>My journeys</a>@endcan
    <a href="{{ route('arcade') }}" @if(request()->routeIs('arcade')) aria-current="page" @endif><span aria-hidden="true">⌘</span>Practice arcade</a>
    <a href="{{ route('challenges.catalog') }}" @if(request()->routeIs('challenges.catalog', 'challenges.show', 'challenges.attempt')) aria-current="page" @endif><span aria-hidden="true">{ }</span>Coding quests</a>
    @can('create', \App\Models\CodingChallenge::class)
    <p class="rail-label">Creator workspace</p>
    @can('create', \App\Models\LearningModule::class)<a href="{{ route('studio.index') }}" @if(request()->routeIs('studio.*')) aria-current="page" @endif><span aria-hidden="true">✦</span>Module studio</a><a href="{{ route('courses.index') }}" @if(request()->routeIs('courses.*', 'curriculum.*')) aria-current="page" @endif><span aria-hidden="true">≡</span>Course builder</a>@endcan
    <a href="{{ route('challenges.index') }}" @if(request()->routeIs('challenges.index', 'challenges.create', 'challenges.edit')) aria-current="page" @endif><span aria-hidden="true">＋</span>Quest studio</a>
    @endcan
    @can('viewAny', \App\Models\User::class)
    <p class="rail-label">{{ auth()->user()->account_role->name }} workspace</p>
    <a href="{{ route('account-governance.index') }}" @if(request()->routeIs('account-governance.*')) aria-current="page" @endif><span aria-hidden="true">◎</span>Community</a>
    <a href="{{ route('content-moderation.index') }}" @if(request()->routeIs('content-moderation.*')) aria-current="page" @endif><span aria-hidden="true">◇</span>Moderation</a>
    @can('viewAny', \App\Models\LearningModule::class)<a href="{{ route('module-reviews.index') }}" @if(request()->routeIs('module-reviews.*', 'course-reviews.*', 'challenge-reviews.*')) aria-current="page" @endif><span aria-hidden="true">✓</span>Publication reviews</a>@endcan
    @can('viewAny', \App\Models\InstructorApplication::class)<a href="{{ route('instructor-reviews.index') }}" @if(request()->routeIs('instructor-reviews.*', 'contributor-reviews.*')) aria-current="page" @endif><span aria-hidden="true">↥</span>Role applications</a>@endcan
    @can('manage', \App\Models\WeeklyEvent::class)<a href="{{ route('weekly-studio.index') }}" @if(request()->routeIs('weekly-studio.*')) aria-current="page" @endif><span aria-hidden="true">▦</span>Weekly planning</a>@endcan
    @can('viewReports', \App\Models\User::class)<a href="{{ route('system-reports') }}" @if(request()->routeIs('system-reports')) aria-current="page" @endif><span aria-hidden="true">▥</span>Platform reports</a><a href="{{ route('finance.index') }}" @if(request()->routeIs('finance.*')) aria-current="page" @endif><span aria-hidden="true">⇄</span>Accounting</a>@endcan
    @endcan
    <p class="rail-label">A little support</p>
    <a href="{{ route('account.show') }}" @if(request()->routeIs('account.*')) aria-current="page" @endif><span aria-hidden="true">◉</span>Profile & security</a>
    <a href="{{ route('help.index') }}" @if(request()->routeIs('help.*')) aria-current="page" @endif><span aria-hidden="true">?</span>Help center</a>
</nav>
