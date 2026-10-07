<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kody — A little play. A lot of possibility.')</title>
    @include('layouts.theme-head')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="learning-page">
    <a class="skip-link" href="#main-content">Skip to content</a>
    @include('layouts.app-header')
    <div class="app-frame {{ auth()->check() ? 'has-workspace' : '' }}">
    @auth<aside class="app-rail"><div class="rail-identity"><span class="rail-emblem" aria-hidden="true">k</span><div><b>Make a little progress.</b><span>{{ auth()->user()->account_role->name }}</span></div></div>@include('layouts.workspace-nav')</aside>
    <details class="mobile-workspace"><summary>Explore your workspace <span aria-hidden="true">⌄</span></summary>@include('layouts.workspace-nav', ['mobile' => true])</details>@endauth
    <main id="main-content" tabindex="-1">@yield('content')</main>
    </div>
    <footer class="site-footer"><a href="{{ route('home') }}" class="play-brand">kody<span class="brand-dot">.</span></a><p>Little steps. Big ideas. Made for curious minds.</p><a href="{{ route('help.index') }}">A little help</a><a href="{{ route('learning.catalog') }}">Find your next adventure ↗︎</a></footer>
</body>
</html>
