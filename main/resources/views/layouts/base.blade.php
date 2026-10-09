<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kody — A little play. A lot of possibility.')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/kody-mark.svg') }}">
    @include('layouts.theme-head')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('page-class', 'learning-page')">
    @include('layouts.feedback')
    <a class="skip-link" href="#main-content">Skip to content</a>
    @include('layouts.app-header')
    <div class="app-frame {{ auth()->check() ? 'has-workspace' : '' }}">
        @include('layouts.workspace')
        <main id="main-content" tabindex="-1" class="@yield('main-class')">
            @include('layouts.page-navigation')
            @yield('content')
        </main>
    </div>
    @yield('footer')
</body>
</html>
