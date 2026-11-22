<template data-focus-navigation>
    <details class="focus-navigation">
        <summary>Menu</summary>
        <nav aria-label="Game navigation">
            @if(!auth()->check() || in_array(auth()->user()->account_role, [\App\Enums\Role::Learner, \App\Enums\Role::Instructor], true))
                <a href="{{ route('home') }}">Tower</a>
            @else
                <a href="{{ route('dashboard') }}">Overview</a>
            @endif
            <a href="{{ route('arcade') }}">Arcade</a>
            <a href="{{ route('learning.catalog') }}">Modules</a>
            <a href="{{ route('course-learning.catalog') }}">Courses</a>
            <a href="{{ route('challenges.catalog') }}">Challenges</a>
            @can('viewLearning', \App\Models\LearningCourse::class)
                <a href="{{ route('course-learning.mine') }}">Library</a>
            @endcan
            <a href="{{ route('help.index') }}">Help</a>
        </nav>
    </details>
</template>
