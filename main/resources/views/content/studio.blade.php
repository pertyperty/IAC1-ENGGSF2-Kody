@extends('layouts.learning')
@section('title', 'Module studio — Kody')
@section('content')

<section class="review-page page-width">
    <div class="studio-heading"><div><p class="overline">INSTRUCTOR WORKSPACE</p><h1>Module studio</h1><p>Write a module, add an assessment, then submit it for review.</p></div><a class="button button-play" href="{{ route('studio.create') }}"><x-action-icon name="plus"/> Create module</a></div>
    <nav class="studio-wayfinding" aria-label="Creator tools"><a href="{{ route('studio.index') }}" aria-current="page"><x-action-icon name="module"/> Modules</a><a href="{{ route('courses.index') }}"><x-action-icon name="course"/> Course builder</a><a href="{{ route('challenges.index') }}"><x-action-icon name="code"/> Coding challenges</a><a href="{{ route('curriculum.index') }}"><x-action-icon name="spark"/> Beginner course plans</a><a href="{{ route('creators.show', auth()->user()) }}"><x-action-icon name="profile"/> Public profile</a><a href="{{ route('earnings.index') }}"><x-action-icon name="wallet"/> Earnings</a></nav>
    <section class="studio-library" aria-labelledby="module-library-heading"><div class="section-heading"><h2 id="module-library-heading">Your modules</h2><span class="status-pill">{{ $modules->total() }} {{ $modules->total() === 1 ? 'module' : 'modules' }}</span></div>
    <x-list-filters :filters="$filters" :statuses="['Draft', 'Published', 'Archived', 'Deleted']"/>
<div class="review-list">@forelse($modules as $module)<a href="{{ route('studio.edit', $module) }}"><b>{{ $module->latestRevision->title }}</b><span>{{ $module->status }} · Revision {{ $module->latestRevision->number }} · {{ $module->latestRevision->review_status }}</span><span class="quiet-link">Open module</span></a>@empty<div class="empty-state"><h3>No modules yet.</h3><p>Create a module or start from an example. Save a draft before submitting it for review.</p></div>@endforelse</div>{{ $modules->links() }}</section>
    <details class="starter-library"><summary>Start from a module example</summary><p>Adapt the explanation and assessment, preview, then save a draft.</p><div class="starter-grid">@foreach(config('creator-examples') as $slug => $example)<a class="starter-card" href="{{ route('studio.create', ['example' => $slug]) }}"><h3>{{ $example['title'] }}</h3><p>{{ $example['description'] }}</p><span>Use this example</span></a>@endforeach</div></details>
</section>
@endsection
