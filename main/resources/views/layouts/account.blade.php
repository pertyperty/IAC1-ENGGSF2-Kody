<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'Kody')</title>
    @include('layouts.theme-head')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="account-page">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="account-header"><a href="{{ url('/') }}" class="brand">Kody<span>.</span></a>
        <nav aria-label="Account navigation">
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}">{{ auth()->check() ? 'Dashboard' : 'Play' }}</a>
            @auth<a href="{{ route('account.show') }}" @if(request()->routeIs('account.show', 'account.edit')) aria-current="page" @endif>My account</a>@endauth
            @can('viewAny', \App\Models\User::class)<a href="{{ route('account-governance.index') }}" @if(request()->routeIs('account-governance.*')) aria-current="page" @endif>Workspace</a>@endcan
            <a href="{{ route('help.index') }}">Help</a>
        </nav>
        @include('layouts.theme-toggle')
    </header>
    <main id="main-content" tabindex="-1" class="account-shell @yield('shell-class')">
        @if (session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
    <footer class="account-footer">Your next programming milestone starts here.</footer>
</body>
</html>
