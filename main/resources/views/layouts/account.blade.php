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
    @include('layouts.app-header')
    <div class="app-frame {{ auth()->check() ? 'has-workspace' : '' }}">
    @auth<aside class="app-rail"><div class="rail-identity"><span class="rail-emblem" aria-hidden="true">k</span><div><b>Make a little progress.</b><span>{{ auth()->user()->account_role->name }}</span></div></div>@include('layouts.workspace-nav')</aside>
    <details class="mobile-workspace"><summary>Explore your workspace <span aria-hidden="true">⌄</span></summary>@include('layouts.workspace-nav', ['mobile' => true])</details>@endauth
    <main id="main-content" tabindex="-1" class="account-shell @yield('shell-class')">
        @if (session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
    </div>
    <footer class="account-footer">Your next programming milestone starts here.</footer>
</body>
</html>
