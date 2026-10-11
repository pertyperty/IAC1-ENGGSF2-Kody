<div class="app-header-shell" data-scroll-navigation>
<span class="header-hover-edge" aria-hidden="true"></span>
<button type="button" class="navigation-reveal" data-navigation-reveal hidden>Show navigation <span aria-hidden="true">⌄</span></button>
<header class="app-header {{ auth()->check() ? 'is-signed-in' : '' }}">
    @auth<details class="mobile-workspace"><summary aria-label="Explore your workspace"><svg width="22" height="22" aria-hidden="true"><use href="{{ asset('images/navigation.svg') }}#menu"/></svg><span class="sr-only">Explore your workspace</span></summary><div class="mobile-workspace-panel"><h2>Your workspace</h2>@include('layouts.workspace-nav', ['mobile' => true])</div></details>@endauth
    @include('layouts.brand')
    <div class="header-page-context">@auth<span class="mobile-player-name">{{ auth()->user()->username }}</span>@endauth<span data-page-context></span></div>
    <nav class="app-primary-nav" aria-label="Main navigation">
        <a href="{{ auth()->check() && auth()->user()->account_role !== \App\Enums\Role::Learner ? route('dashboard') : route('home') }}" @if(request()->routeIs('home', 'dashboard', 'tower.*', 'learning.show', 'arcade')) aria-current="page" @endif>@can('viewAny', \App\Models\User::class)Workspace @else Play @endcan</a>
        <a href="{{ route('learning.catalog') }}" @if(request()->routeIs('learning.catalog', 'course-learning.mine', 'course-learning.show', 'course-learning.lesson', 'challenges.catalog', 'challenges.show', 'modules.*')) aria-current="page" @endif>Learn</a>
        @can('create', \App\Models\LearningModule::class)<a href="{{ route('studio.index') }}" @if(request()->routeIs('studio.*', 'courses.*', 'curriculum.*')) aria-current="page" @endif>Create</a>
        @elsecan('create', \App\Models\CodingChallenge::class)<a href="{{ route('challenges.index') }}" @if(request()->routeIs('challenges.*') && !request()->routeIs('challenges.catalog', 'challenges.show')) aria-current="page" @endif>Create</a>
        @endcan
        <a href="{{ route('course-learning.catalog') }}" @if(request()->routeIs('course-learning.catalog')) aria-current="page" @endif>Courses</a>
        @guest<a href="{{ route('help.index') }}" @if(request()->routeIs('help.*')) aria-current="page" @endif>Help</a>@endguest
    </nav>
    <div class="app-header-actions">
        @auth
        <a class="header-updates" href="{{ route('notifications.index') }}" aria-label="Updates, {{ $chrome['unread'] }} unread"><svg width="20" height="20" aria-hidden="true"><use href="{{ asset('images/navigation.svg') }}#bell"/></svg><span class="updates-label">Updates</span> @if($chrome['unread'])<span class="unread-badge">{{ $chrome['unread'] }}</span>@endif</a>
        <details class="account-menu">
            <summary aria-label="Account menu">@include('layouts.avatar', ['avatarUser' => auth()->user(), 'avatarClass' => 'header-avatar'])<span class="header-username">{{ auth()->user()->username }}</span><span aria-hidden="true">⌄</span></summary>
            <nav aria-label="Account navigation">
                <span class="menu-role">{{ auth()->user()->account_role->name }}</span>
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('account.show') }}">My account</a>
                <a href="{{ route('notifications.index') }}">Updates</a>
                @can('viewAny', \App\Models\User::class)<a href="{{ route('account-governance.index') }}">Workspace</a>@endcan
                <a href="{{ route('help.index') }}">Help</a>
                <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="menu-signout">Sign out</button></form>
            </nav>
        </details>
        @else
        <a class="header-login" href="{{ route('login') }}">Log in</a>
        <a class="button button-dark button-small header-join" href="{{ route('register') }}">Join Kody</a>
        @endauth
        @include('layouts.theme-toggle')
        <button type="button" class="header-tour" data-tour-open hidden aria-label="Kody guide and companion" title="Kody guide and companion"><x-kody-art size="32"/></button>
    </div>
</header>
@include('layouts.progress-strip')
</div>
