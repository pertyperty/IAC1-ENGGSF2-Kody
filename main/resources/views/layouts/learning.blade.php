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
    <header class="site-header">
        <a href="{{ route('home') }}" class="play-brand" aria-label="Kody home"><span class="brand-mark" aria-hidden="true">k</span>kody<span class="brand-dot">.</span></a>
        <nav aria-label="Main navigation"><a href="{{ auth()->check() ? route('dashboard') : route('home') }}" @if(request()->routeIs('home', 'dashboard')) aria-current="page" @endif>Play</a><a href="{{ route('learning.catalog') }}" @if(request()->routeIs('learning.*', 'course-learning.*', 'challenges.catalog', 'challenges.show')) aria-current="page" @endif>Learn</a><a href="{{ auth()->check() && auth()->user()->can('create', \App\Models\LearningModule::class) ? route('studio.index') : (auth()->check() && auth()->user()->can('create', \App\Models\CodingChallenge::class) ? route('challenges.index') : route('home').'#creators') }}">Create</a>@can('viewAny', \App\Models\LearningModule::class)<a href="{{ route('module-reviews.index') }}">Review</a>@endcan</nav>
        <div class="nav-actions">@can('viewAny', \App\Models\User::class)<a class="nav-login" href="{{ route('account-governance.index') }}">Workspace</a>@endcan @auth<a class="nav-login" href="{{ route('notifications.index') }}">Updates</a><a class="nav-login" href="{{ route('account.show') }}">My account</a>@else<a class="nav-login" href="{{ route('login') }}">Log in</a><a class="button button-dark button-small" href="{{ route('register') }}">Join the adventure <span aria-hidden="true">↗︎</span></a>@endauth</div>
        @include('layouts.theme-toggle')
    </header>
    <main id="main-content" tabindex="-1">@yield('content')</main>
    <footer class="site-footer"><a href="{{ route('home') }}" class="play-brand">kody<span class="brand-dot">.</span></a><p>Little steps. Big ideas. Made for curious minds.</p><a href="{{ route('help.index') }}">A little help</a><a href="{{ route('learning.catalog') }}">Find your next adventure ↗︎</a></footer>
</body>
</html>
