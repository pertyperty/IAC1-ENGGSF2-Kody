@extends('layouts.learning')
@section('title', 'Weekly leaderboard — Kody')
@section('content')
<section class="page-width quest-page">
    @include('weekly.navigation')
    <header class="quest-hero">@include('weekly.scenery')<div><p class="overline">A NEW CHALLENGE. ITS OWN CLIMB.</p><h1>Weekly leaderboard.</h1><p>Celebrate each week’s verified solutions. Weekly scores have their own rankings, separate from lifetime learning XP.</p></div><x-kody-art name="celebration" size="160"/></header>
    <section data-weekly-archive aria-labelledby="weekly-archive-title"><div class="panel-heading"><h2 id="weekly-archive-title">Choose a completed week</h2><span class="step-pill">{{ number_format($events->total()) }} published</span></div>
    <div class="weekly-archive-grid">@forelse($events as $event)
        <article class="content-panel weekly-event-card"><p class="overline">{{ \Carbon\CarbonImmutable::parse($event->starts_at)->setTimezone('Asia/Manila')->format('M j, Y') }}</p><h3>{{ $event->title }}</h3><p>{{ config('challenges.languages.'.$event->language) }} · {{ $event->participants }} players</p><p class="field-hint">Published {{ \Carbon\CarbonImmutable::parse($event->published_at)->setTimezone('Asia/Manila')->format('M j, Y') }} · Manila</p><a class="button button-play" href="{{ route('weekly-results.show', $event->id) }}"><x-action-icon name="chart"/> View weekly scores</a></article>
    @empty<x-empty-state title="The first weekly podium is waiting" description="Scores appear after the week closes, all committed evaluations finish and staff publishes the verified results." :action-url="route('weekly-events.index')" action-label="Explore weekly quests"/>@endforelse</div>{{ $events->links() }}</section>
</section>
@endsection
