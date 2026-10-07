<header class="app-header">
    <a href="{{ route('home') }}" class="play-brand" aria-label="Kody home"><span class="brand-mark" aria-hidden="true">k</span>kody<span class="brand-dot">.</span></a>
    <nav class="app-primary-nav" aria-label="Main navigation">
        <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" @if(request()->routeIs('home', 'dashboard', 'learning.show', 'arcade')) aria-current="page" @endif>Play</a>
        <a href="{{ route('learning.catalog') }}" @if(request()->routeIs('learning.catalog', 'course-learning.*', 'challenges.catalog', 'challenges.show', 'modules.*')) aria-current="page" @endif>Learn</a>
        @can('create', \App\Models\LearningModule::class)<a href="{{ route('studio.index') }}" @if(request()->routeIs('studio.*', 'courses.*', 'curriculum.*')) aria-current="page" @endif>Create</a>
        @elsecan('create', \App\Models\CodingChallenge::class)<a href="{{ route('challenges.index') }}" @if(request()->routeIs('challenges.*') && !request()->routeIs('challenges.catalog', 'challenges.show')) aria-current="page" @endif>Create</a>
        @endcan
        @guest<a href="{{ route('help.index') }}" @if(request()->routeIs('help.*')) aria-current="page" @endif>Help</a>@endguest
    </nav>
    <div class="app-header-actions">
        @auth
        <details class="account-menu">
            <summary aria-label="Account menu"><span class="header-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->username ?? 'K', 0, 1)) }}</span><span>Account</span><span aria-hidden="true">⌄</span></summary>
            <nav aria-label="Account navigation">
                <span class="menu-role">{{ auth()->user()->account_role->name }}</span>
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('account.show') }}">My account</a>
                <a href="{{ route('notifications.index') }}">Updates</a>
                @can('viewAny', \App\Models\User::class)<a href="{{ route('account-governance.index') }}">Workspace</a>@endcan
                <a href="{{ route('help.index') }}">Help</a>
            </nav>
        </details>
        @else
        <a class="header-login" href="{{ route('login') }}">Log in</a>
        <a class="button button-dark button-small header-join" href="{{ route('register') }}">Join Kody</a>
        @endauth
        @include('layouts.theme-toggle')
    </div>
</header>
