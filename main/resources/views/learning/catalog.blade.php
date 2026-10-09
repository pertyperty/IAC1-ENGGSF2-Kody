@extends('layouts.learning')

@section('title', 'Learning adventures — Kody')

@section('content')

    <section class="module-section page-width" aria-labelledby="modules-heading">

        <div class="section-heading"><div><p class="overline">YOUR NEXT “AHA!” MOMENT</p><h1 id="modules-heading">Pick a little adventure.</h1></div><p>Real programming ideas.<br>Small games. Room to experiment.</p></div>

        <form method="get" action="{{ route('learning.catalog') }}" class="catalog-search catalog-filter-panel">
            <div class="filter-grid">
                <div><label for="module-search">Find your next idea</label><input id="module-search" type="search" name="q" value="{{ $query }}" maxlength="80" placeholder="Try loops, crystals, or first steps"></div>
                <div><label for="module-template">Play style</label><select id="module-template" name="template"><option value="">All games and quizzes</option>@foreach($templates as $slug => $label)<option value="{{ $slug }}" @selected($template === $slug)>{{ $label }}</option>@endforeach</select></div>
            </div>
            <div class="filter-actions"><button class="button button-dark button-small" type="submit">Find adventures</button>@if($query !== '' || $template)<a class="quiet-link" href="{{ route('learning.catalog') }}">Clear filters</a>@endif</div>
        </form>

        <p><a class="button button-dark button-small" href="{{ route('course-learning.catalog') }}">Explore courses →</a> @can('viewLearning', \App\Models\LearningCourse::class) <a class="button button-secondary button-small" href="{{ route('course-learning.mine') }}">Your journeys</a> @endcan</p>
        <div class="module-grid">
            @forelse($modules as $slug => $module)

                <a class="module-card {{ $module['color'] }}" href="{{ route('learning.show', $slug) }}"><div class="module-art" aria-hidden="true"><span class="module-symbol">{{ $module['icon'] }}</span><span class="orbit orbit-one"></span><span class="orbit orbit-two"></span><span class="art-star">✦</span><span class="art-dot"></span></div><div class="module-copy"><div class="module-meta"><span>{{ $module['concept'] }}</span><span>{{ $module['duration'] }} · Beginner</span></div><h2>{{ $module['title'] }}</h2><p>{{ $module['description'] }}</p><span class="module-link">@auth Play this module @else Sign in to explore @endauth <span aria-hidden="true">↗︎</span></span></div></a>

            @empty @if($published->isEmpty())<x-empty-state title="No adventures found" description="Try a broader idea or clear the filters to explore every adventure." :action-url="route('learning.catalog')" action-label="See all modules" />@endif @endforelse
            @foreach($published as $revision)

                <a class="module-card mint" href="{{ route('modules.show', $revision->module_id) }}"><div class="module-art" aria-hidden="true"><span class="module-symbol">✦</span></div><div class="module-copy"><div class="module-meta"><span>{{ $templates[$revision->assessment['template'] ?? ''] ?? 'Creator adventure' }}</span><span>{{ $revision->type }}</span></div><h2>{{ $revision->title }}</h2><p class="content-price">{{ $revision->module->creator?->account_status === \App\Enums\AccountStatus::Deleted ? 'Free · retained creator content' : ($revision->price_kb ? $revision->price_kb.' KB' : 'Free') }}@if($revision->minimum_xp > 0) · {{ $revision->minimum_xp }} XP to unlock
@endif</p><p>{{ \Illuminate\Support\Str::limit($revision->description, 180) }}</p><span class="module-link">@auth Explore this adventure @else Sign in to explore @endauth ↗</span></div></a>
            @endforeach

        </div>

        {{ $published->links() }}

        <p class="catalog-note">Explore our starter trails and adventures made by Kody creators.</p>

        @guest<p class="catalog-note">The <a href="{{ route('home') }}#try-it">first game</a> is open to everyone. <a href="{{ route('login') }}">Sign in</a> or <a href="{{ route('register') }}">create an account</a> to explore modules.</p>@endguest

    </section>

@endsection
