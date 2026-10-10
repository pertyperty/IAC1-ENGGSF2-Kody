<nav class="workspace-nav" aria-label="{{ ($mobile ?? false) ? 'Mobile workspace' : 'Your workspace' }}">
    <p class="rail-label">@can('viewAny', \App\Models\User::class)Explore Kody @else Your playground @endcan</p>
    <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard', 'learning.show')) aria-current="page" @endif><span aria-hidden="true">◈</span>Overview</a>
    <a href="{{ route('learning.catalog') }}" @if(request()->routeIs('learning.catalog', 'modules.*', 'course-learning.show', 'course-learning.lesson')) aria-current="page" @endif><span aria-hidden="true">▤</span>Learning catalog</a>
    <a href="{{ route('course-learning.catalog') }}" @if(request()->routeIs('course-learning.catalog')) aria-current="page" @endif><span aria-hidden="true">≡</span>Browse courses</a>
    @can('viewLearning', \App\Models\LearningCourse::class)<a href="{{ route('course-learning.mine') }}" @if(request()->routeIs('course-learning.mine')) aria-current="page" @endif><span aria-hidden="true">↗</span>My journeys</a>@endcan
    @can('viewLearning', \App\Models\LearningCourse::class)<a href="{{ route('weekly-events.index') }}" @if(request()->routeIs('weekly-events.*')) aria-current="page" @endif><span aria-hidden="true">▦</span>Weekly quests</a><a href="{{ route('leaderboards.index') }}" @if(request()->routeIs('leaderboards.*')) aria-current="page" @endif><span aria-hidden="true">↗</span>Leaderboards</a>@endcan
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
    @can('viewAny', \App\Models\LearningModule::class)<a href="{{ route('module-reviews.index') }}" @if(request()->routeIs('module-reviews.*')) aria-current="page" @endif><span aria-hidden="true">✓</span>Module reviews</a>@endcan
    @can('viewAny', \App\Models\LearningCourse::class)<a href="{{ route('course-reviews.index') }}" @if(request()->routeIs('course-reviews.*')) aria-current="page" @endif><span aria-hidden="true">≡</span>Course reviews</a>@endcan
    @can('viewAny', \App\Models\CodingChallenge::class)<a href="{{ route('challenge-reviews.index') }}" @if(request()->routeIs('challenge-reviews.*')) aria-current="page" @endif><span aria-hidden="true">{ }</span>Challenge reviews</a>@endcan
    @can('viewAny', \App\Models\InstructorApplication::class)<a href="{{ route('instructor-reviews.index') }}" @if(request()->routeIs('instructor-reviews.*')) aria-current="page" @endif><span aria-hidden="true">↥</span>Instructor applications</a>@endcan
    @can('viewAny', \App\Models\ContributorApplication::class)<a href="{{ route('contributor-reviews.index') }}" @if(request()->routeIs('contributor-reviews.*')) aria-current="page" @endif><span aria-hidden="true">＋</span>Contributor applications</a>@endcan
    @can('manage', \App\Models\WeeklyEvent::class)<a href="{{ route('weekly-studio.index') }}" @if(request()->routeIs('weekly-studio.*')) aria-current="page" @endif><span aria-hidden="true">▦</span>Weekly planning</a>@endcan
    @can('viewReports', \App\Models\User::class)<a href="{{ route('system-reports') }}" @if(request()->routeIs('system-reports')) aria-current="page" @endif><span aria-hidden="true">▥</span>Platform reports</a><a href="{{ route('finance.index') }}" @if(request()->routeIs('finance.*')) aria-current="page" @endif><span aria-hidden="true">⇄</span>Accounting</a>@endcan
    @endcan
    <p class="rail-label">A little support</p>
    @can('viewAny', \App\Models\GamePreset::class)<a href="{{ route('game-presets.index') }}" @if(request()->routeIs('game-presets.*')) aria-current="page" @endif><span aria-hidden="true">⌘</span>Game presets</a>@endcan
    @can('viewAny', \App\Models\FaqEntry::class)<a href="{{ route('faq-management.index') }}" @if(request()->routeIs('faq-management.*')) aria-current="page" @endif><span aria-hidden="true">?</span>FAQ management</a>@endcan
    @can('viewAny', \App\Models\User::class)<a href="{{ route('creator-erasure.index') }}" @if(request()->routeIs('creator-erasure.*')) aria-current="page" @endif><span aria-hidden="true">◇</span>Privacy reviews</a>@endcan
    <a href="{{ route('notifications.index') }}" @if(request()->routeIs('notifications.*')) aria-current="page" @endif><span aria-hidden="true">✉</span>Updates</a>
    <a href="{{ route('wallet.index') }}" @if(request()->routeIs('wallet.*', 'earnings.*')) aria-current="page" @endif><span aria-hidden="true">◈</span>Wallet</a>
    <a href="{{ route('account.show') }}" @if(request()->routeIs('account.*')) aria-current="page" @endif><span aria-hidden="true">◉</span>Profile & security</a>
    <a href="{{ route('help.index') }}" @if(request()->routeIs('help.*')) aria-current="page" @endif><span aria-hidden="true">?</span>Help center</a>
</nav>
