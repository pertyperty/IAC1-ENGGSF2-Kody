<nav class="workspace-nav" aria-label="{{ ($mobile ?? false) ? 'Mobile workspace' : 'Your workspace' }}">
    <p class="rail-label">Play</p>
    @if(in_array(auth()->user()->account_role, [\App\Enums\Role::Learner, \App\Enums\Role::Instructor], true))<a href="{{ route('home') }}" @if(request()->routeIs('home', 'tower.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'tower', 'label' => 'Tower'])</a>@endif
    <a href="{{ route('arcade') }}" @if(request()->routeIs('arcade')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'game', 'label' => 'Arcade'])</a>
    <p class="rail-label">Learn</p>
    <a href="{{ route('learning.catalog') }}" @if(request()->routeIs('learning.catalog', 'modules.*', 'learning.show')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'module', 'label' => 'Modules'])</a>
    <a href="{{ route('course-learning.catalog') }}" @if(request()->routeIs('course-learning.catalog', 'course-learning.show', 'course-learning.lesson')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'course', 'label' => 'Courses'])</a>
    <a href="{{ route('challenges.catalog') }}" @if(request()->routeIs('challenges.catalog', 'challenges.show', 'challenges.attempt')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'code', 'label' => 'Challenges'])</a>
    <p class="rail-label">My progress</p>
    <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'panel', 'label' => auth()->user()->account_role === \App\Enums\Role::Learner ? 'Progress' : 'Overview'])</a>
    @can('viewLearning', \App\Models\LearningCourse::class)
    <a href="{{ route('course-learning.mine') }}" @if(request()->routeIs('course-learning.mine')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'journey', 'label' => 'Library'])</a>

    <a href="{{ route('weekly-events.index') }}" @if(request()->routeIs('weekly-events.*', 'weekly-leaderboards.*', 'weekly-results.show')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'game', 'label' => 'Weekly'])</a>
    <a href="{{ route('leaderboards.index') }}" @if(request()->routeIs('leaderboards.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'chart', 'label' => 'Rankings'])</a>
    @endcan
    @can('create', \App\Models\CodingChallenge::class)
    <p class="rail-label">Create</p>
    <a href="{{ route(auth()->user()->can('create', \App\Models\LearningModule::class) ? 'studio.index' : 'challenges.index') }}" @if(request()->routeIs('studio.*', 'courses.*', 'challenges.index', 'challenges.create', 'challenges.edit', 'curriculum.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'create', 'label' => 'Studio'])</a>
    @endcan
    @can('viewAny', \App\Models\User::class)
    <p class="rail-label">{{ auth()->user()->account_role->name }} workspace</p>
    <a href="{{ route('account-governance.index') }}" @if(request()->routeIs('account-governance.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'people', 'label' => 'Community'])</a>
    <a href="{{ route('content-moderation.index') }}" @if(request()->routeIs('content-moderation.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'shield', 'label' => 'Moderation'])</a>
    @can('viewAny', \App\Models\LearningModule::class)<a href="{{ route('review-inbox') }}" @if(request()->routeIs('review-inbox', 'module-reviews.*', 'course-reviews.*', 'challenge-reviews.*', 'instructor-reviews.*', 'contributor-reviews.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'shield', 'label' => 'Reviews'])</a>@endcan
    @can('manage', \App\Models\WeeklyEvent::class)<a href="{{ route('weekly-studio.index') }}" @if(request()->routeIs('weekly-studio.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'journey', 'label' => 'Schedule'])</a>@endcan
    @can('viewReports', \App\Models\User::class)<a href="{{ route('system-reports') }}" @if(request()->routeIs('system-reports')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'chart', 'label' => 'Reports'])</a><a href="{{ route('finance.index') }}" @if(request()->routeIs('finance.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'wallet', 'label' => 'Accounting'])</a>@endcan
    @endcan
    @can('manage', \App\Models\TowerLevel::class)<a href="{{ route('tower-studio.index') }}" @if(request()->routeIs('tower-studio.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'tower', 'label' => 'Tower'])</a>@endcan
    @can('manageSite', \App\Models\User::class)<a href="{{ route('site-management.index') }}" @if(request()->routeIs('site-management.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'settings', 'label' => 'Settings'])</a>@endcan
    <p class="rail-label">Help and account</p>
    @can('viewAny', \App\Models\GamePreset::class)<a href="{{ route('game-presets.index') }}" @if(request()->routeIs('game-presets.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'game', 'label' => 'Presets'])</a>@endcan
    @can('viewAny', \App\Models\User::class)<a href="{{ route('creator-erasure.index') }}" @if(request()->routeIs('creator-erasure.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'shield', 'label' => 'Privacy'])</a>@endcan
    <a href="{{ route('notifications.index') }}" @if(request()->routeIs('notifications.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'bell', 'label' => 'Notifications'])</a>
    <a href="{{ route('wallet.index') }}" @if(request()->routeIs('wallet.*', 'earnings.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'wallet', 'label' => 'Wallet'])</a>
    <a href="{{ route('account.show') }}" @if(request()->routeIs('account.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'profile', 'label' => 'Profile'])</a>
    <a href="{{ route('help.index') }}" @if(request()->routeIs('help.*', 'faq-management.*')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'help', 'label' => 'Help'])</a>

    <a href="{{ route('contact') }}" @if(request()->routeIs('contact')) aria-current="page" @endif>@include('layouts.navigation-item-content', ['icon' => 'mail', 'label' => 'Contact'])</a>

</nav>
