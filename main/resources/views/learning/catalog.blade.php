@extends('layouts.learning')

@section('title', 'Modules — Kody')

@section('content')

    <section class="module-section page-width">

        <x-catalog-header title="Modules" description="Learn one concept, practice it, and check your understanding.">
            <a class="quiet-link" href="{{ route('course-learning.catalog') }}">Courses</a>
            @can('viewLearning', \App\Models\LearningCourse::class)
                <a class="quiet-link" href="{{ route('course-learning.mine') }}">My library</a>
            @endcan
        </x-catalog-header>

        <form method="get" action="{{ route('learning.catalog') }}" class="catalog-search catalog-filter-panel">
            <div class="filter-grid">
                <div><label for="module-search">Search modules</label><input id="module-search" type="search" name="q" value="{{ $query }}" maxlength="80" placeholder="Try loops, crystals, or first steps"></div>
                <div><label for="module-template">Activity type</label><select id="module-template" name="template"><option value="">All games and quizzes</option>@foreach($templates as $slug => $label)<option value="{{ $slug }}" @selected($template === $slug)>{{ $label }}</option>@endforeach</select></div>
            </div>
            <div class="filter-actions"><button class="button button-dark button-small" type="submit">Search</button>@if($query !== '' || $template)<a class="quiet-link" href="{{ route('learning.catalog') }}">Clear filters</a>@endif</div>
        </form>

        <div class="catalog-results">
            @forelse($modules as $slug => $module)
                <x-catalog-entry :title="$module['title']" :url="route('learning.show', $slug)" :action="auth()->check() ? 'Open module' : 'Sign in to explore'" icon="book">
                    <x-slot:meta><span>{{ $module['concept'] }}</span><span>Beginner</span><span>{{ $module['duration'] }}</span></x-slot:meta>
                    <p>{{ $module['description'] }}</p>
                    <span class="creator-byline">By Kody</span>
                    @if($starterProgress)
                        @include('learning.saved-progress', ['saved' => ['state' => $starterProgress['levels'][$slug]['completed'] ? 'complete' : 'new', 'label' => $starterProgress['levels'][$slug]['completed'] ? 'Completed' : ($starterProgress['levels'][$slug]['unlocked'] ? 'Ready to play' : 'Locked: clear the previous game')]])
                    @endif
                </x-catalog-entry>
            @empty
                @if($published->isEmpty())<x-empty-state title="No modules found" description="Try another search or clear the filters." :action-url="route('learning.catalog')" action-label="See all modules" />@endif
            @endforelse
            @foreach($published as $revision)
                <x-catalog-entry :title="$revision->title" :url="route('modules.show', $revision->module_id)" :action="auth()->check() ? 'Open module' : 'Sign in to explore'" icon="book">
                    <x-slot:meta><span>{{ $templates[$revision->assessment_template ?? ''] ?? 'Creator module' }}</span><span>{{ $revision->type }}</span></x-slot:meta>
                    <p>{{ \Illuminate\Support\Str::limit($revision->description, 180) }}</p>
                    <x-creator-byline :creator="$revision->module->creator"/>
                    @if($moduleProgress->has($revision->module_id))@include('learning.saved-progress', ['saved' => $moduleProgress->get($revision->module_id)])@endif
                    <p class="content-price">{{ $revision->module->creator?->account_status === \App\Enums\AccountStatus::Deleted ? 'Free · retained creator content' : ($revision->price_kb ? $revision->price_kb.' KB' : 'Free') }}@if($revision->minimum_xp > 0) · {{ $revision->minimum_xp }} XP to unlock @endif</p>
                </x-catalog-entry>
            @endforeach
        </div>

        {{ $published->links() }}
    </section>
@endsection
