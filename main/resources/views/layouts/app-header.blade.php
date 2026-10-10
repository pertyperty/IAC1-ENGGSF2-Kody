<div class="app-header-shell" data-scroll-navigation>
@include('layouts.progress-strip')
<span class="header-hover-edge" aria-hidden="true"></span>
<button type="button" class="navigation-reveal" data-navigation-reveal hidden>Show navigation <span aria-hidden="true">⌄</span></button>
<header class="app-header">
    <a href="{{ route('home') }}" class="play-brand" aria-label="Kody home"><img class="brand-mark" src="{{ asset('images/kody-mark.svg') }}" width="36" height="36" alt="">kody<span class="brand-dot">.</span></a>
    <nav class="app-primary-nav" aria-label="Main navigation">
        <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" @if(request()->routeIs('home', 'dashboard', 'learning.show', 'arcade')) aria-current="page" @endif>@can('viewAny', \App\Models\User::class)Workspace @else Play @endcan</a>
        <a href="{{ route('learning.catalog') }}" @if(request()->routeIs('learning.catalog', 'course-learning.mine', 'course-learning.show', 'course-learning.lesson', 'challenges.catalog', 'challenges.show', 'modules.*')) aria-current="page" @endif>Learn</a>
        @can('create', \App\Models\LearningModule::class)<a href="{{ route('studio.index') }}" @if(request()->routeIs('studio.*', 'courses.*', 'curriculum.*')) aria-current="page" @endif>Create</a>
        @elsecan('create', \App\Models\CodingChallenge::class)<a href="{{ route('challenges.index') }}" @if(request()->routeIs('challenges.*') && !request()->routeIs('challenges.catalog', 'challenges.show')) aria-current="page" @endif>Create</a>
        @endcan
        <a href="{{ route('course-learning.catalog') }}" @if(request()->routeIs('course-learning.catalog')) aria-current="page" @endif>Courses</a>
        @guest<a href="{{ route('help.index') }}" @if(request()->routeIs('help.*')) aria-current="page" @endif>Help</a>@endguest
    </nav>
    <div class="app-header-actions">
        @auth
        <a class="header-updates" href="{{ route('notifications.index') }}" aria-label="Updates, {{ $chrome['unread'] }} unread">Updates @if($chrome['unread'])<span class="unread-badge">{{ $chrome['unread'] }}</span>@endif</a>
        <details class="account-menu">
            <summary aria-label="Account menu"><span class="header-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->username ?? 'K', 0, 1)) }}</span><span>Account</span><span aria-hidden="true">⌄</span></summary>
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
    </div>
</header>
</div>
