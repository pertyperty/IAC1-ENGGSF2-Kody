@extends('layouts.learning')
@section('title', 'Learning adventures — Kody')
@section('content')
    <section class="module-section page-width" aria-labelledby="modules-heading">
        <div class="section-heading"><div><p class="overline">YOUR NEXT “AHA!” MOMENT</p><h1 id="modules-heading">Pick a little adventure.</h1></div><p>Real programming ideas.<br>Small games. Room to experiment.</p></div>
        <form method="get" action="{{ route('learning.catalog') }}" class="catalog-search"><label for="module-search">Find your next idea</label><div><input id="module-search" type="search" name="q" value="{{ $query }}" maxlength="80" placeholder="Try loops, crystals, or first steps"><button class="button button-dark button-small" type="submit">Search</button></div></form>
        <div class="module-grid">
            @forelse($modules as $slug => $module)
                <a class="module-card {{ $module['color'] }}" href="{{ route('learning.show', $slug) }}"><div class="module-art" aria-hidden="true"><span class="module-symbol">{{ $module['icon'] }}</span><span class="orbit orbit-one"></span><span class="orbit orbit-two"></span><span class="art-star">✦</span><span class="art-dot"></span></div><div class="module-copy"><div class="module-meta"><span>{{ $module['concept'] }}</span><span>{{ $module['duration'] }} · Beginner</span></div><h2>{{ $module['title'] }}</h2><p>{{ $module['description'] }}</p><span class="module-link">@auth Play this module @else Sign in to explore @endauth <span aria-hidden="true">↗︎</span></span></div></a>
            @empty<p class="empty-catalog">No adventures match that search. <a href="{{ route('learning.catalog') }}">See all modules →</a></p>@endforelse
        </div>
        <p class="catalog-note">These three practice modules use the first Kody game template. Creator-published modules will join this catalog as publishing tools are added.</p>
        @guest<p class="catalog-note">The <a href="{{ route('home') }}#try-it">first game</a> is open to everyone. <a href="{{ route('login') }}">Sign in</a> or <a href="{{ route('register') }}">create an account</a> to explore modules.</p>@endguest
    </section>
@endsection
