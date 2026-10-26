@extends('layouts.learning')

@section('title', 'Lessons — Kody')

@section('content')

    <section class="module-section page-width" aria-labelledby="modules-heading">

        <div class="section-heading"><div><p class="overline">LESSONS</p><h1 id="modules-heading">Find a lesson</h1></div><p>Learn a concept, practise it, and check your understanding.</p></div>

        <form method="get" action="{{ route('learning.catalog') }}" class="catalog-search catalog-filter-panel">
            <div class="filter-grid">
                <div><label for="module-search">Search lessons</label><input id="module-search" type="search" name="q" value="{{ $query }}" maxlength="80" placeholder="Try loops, crystals, or first steps"></div>
                <div><label for="module-template">Activity type</label><select id="module-template" name="template"><option value="">All games and quizzes</option>@foreach($templates as $slug => $label)<option value="{{ $slug }}" @selected($template === $slug)>{{ $label }}</option>@endforeach</select></div>
            </div>
            <div class="filter-actions"><button class="button button-dark button-small" type="submit">Search</button>@if($query !== '' || $template)<a class="quiet-link" href="{{ route('learning.catalog') }}">Clear filters</a>@endif</div>
        </form>

        <p><a class="button button-dark button-small" href="{{ route('course-learning.catalog') }}">Explore courses <x-action-icon name="right"/></a> @can('viewLearning', \App\Models\LearningCourse::class) <a class="button button-secondary button-small" href="{{ route('course-learning.mine') }}">Your courses</a> @endcan</p>
        <div class="module-grid">
            @forelse($modules as $slug => $module)

                <a class="module-card {{ $module['color'] }}" href="{{ route('learning.show', $slug) }}"><div class="module-art" aria-hidden="true"><span class="module-symbol"><x-action-icon :name="$module['icon']"/></span><span class="orbit orbit-one"></span><span class="orbit orbit-two"></span><span class="art-star"><x-action-icon name="spark"/></span><span class="art-dot"></span></div><div class="module-copy"><div class="module-meta"><span>{{ $module['concept'] }}</span><span>{{ $module['duration'] }} · Beginner</span></div><h2>{{ $module['title'] }}</h2>@if($starterProgress)@include('learning.saved-progress', ['saved' => ['state' => $starterProgress['levels'][$slug]['completed'] ? 'complete' : 'new', 'label' => $starterProgress['levels'][$slug]['completed'] ? 'Completed · game cleared' : ($starterProgress['levels'][$slug]['unlocked'] ? 'Ready to play' : 'Locked · clear previous game')]])@endif<p>{{ $module['description'] }}</p><span class="module-link">@auth Open lesson @else Sign in to explore @endauth <span aria-hidden="true"><x-action-icon name="right"/></span></span></div></a>

            @empty @if($published->isEmpty())<x-empty-state title="No lessons found" description="Try another search or clear the filters." :action-url="route('learning.catalog')" action-label="See all modules" />@endif @endforelse
            @foreach($published as $revision)

                <a class="module-card mint" href="{{ route('modules.show', $revision->module_id) }}"><div class="module-art" aria-hidden="true"><span class="module-symbol"><x-action-icon name="spark"/></span></div><div class="module-copy"><div class="module-meta"><span>{{ $templates[$revision->assessment['template'] ?? ''] ?? 'Creator lesson' }}</span><span>{{ $revision->type }}</span></div><h2>{{ $revision->title }}</h2><x-creator-byline :creator="$revision->module->creator" :linked="false"/>@if($moduleProgress->has($revision->module_id))@include('learning.saved-progress', ['saved' => $moduleProgress->get($revision->module_id)])@endif<p class="content-price">{{ $revision->module->creator?->account_status === \App\Enums\AccountStatus::Deleted ? 'Free · retained creator content' : ($revision->price_kb ? $revision->price_kb.' KB' : 'Free') }}@if($revision->minimum_xp > 0) · {{ $revision->minimum_xp }} XP to unlock
@endif</p><p>{{ \Illuminate\Support\Str::limit($revision->description, 180) }}</p><span class="module-link">@auth Open lesson @else Sign in to explore @endauth <x-action-icon name="right"/></span></div></a>
            @endforeach

        </div>

        {{ $published->links() }}

        <p class="catalog-note">Lessons from Kody and community creators.</p>

        @guest<p class="catalog-note">The <a href="{{ route('home') }}#try-it">first game</a> is open to everyone. <a href="{{ route('login') }}">Sign in</a> or <a href="{{ route('register') }}">create an account</a> to explore modules.</p>@endguest

    </section>

@endsection
