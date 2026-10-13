<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Learn programming through interactive coding games, quizzes and creator-made lessons. Climb the Kody Tower one discovery at a time.">
    @if(auth()->check() || request()->routeIs('login*', 'register*', 'account.*', 'recovery.*', 'verification.*'))<meta name="robots" content="noindex, nofollow">@endif
    <title>@yield('title', 'Kody — A little play. A lot of possibility.')</title>
    @include('layouts.favicon')
    @include('layouts.theme-head')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('page-class', 'learning-page')" data-icon-sprite="{{ asset('images/navigation.svg') }}" data-kodebit-icon="{{ asset(config('branding.assets.kodebit')) }}" style="--kody-mascot: url('{{ asset(config('branding.assets.mascot')) }}')">
    @include('layouts.feedback')
    <a class="skip-link" href="#main-content">Skip to content</a>
    @include('layouts.app-header')
    <div class="app-frame {{ auth()->check() ? 'has-workspace' : '' }}">
        @include('layouts.workspace')
        <main id="main-content" tabindex="-1" class="@yield('main-class')">
            @include('layouts.page-navigation')
            @if($chrome['announcement'])<aside class="site-announcement" aria-label="Kody announcement"><x-action-icon name="updates"/><div><b>{{ $chrome['announcement']->title }}</b><p>{{ $chrome['announcement']->message }}</p></div>@if($chrome['announcement']->destination !== 'none')<a class="quiet-link" href="{{ route(['help' => 'help.index', 'contact' => 'contact', 'learn' => 'learning.catalog'][$chrome['announcement']->destination]) }}">{{ ['help' => 'Help center', 'contact' => 'Contact Kody', 'learn' => 'Explore learning'][$chrome['announcement']->destination] }} <x-action-icon name="right"/></a>@endif</aside>@endif
            @if(session('status'))<details class="action-receipt" open><summary>Latest update</summary><p>{{ session('status') }}</p></details>@endif
            @yield('content')
        </main>
    </div>
    @yield('footer')
    @include('layouts.kody-companion')
    @include('layouts.role-update')
</body>
</html>
