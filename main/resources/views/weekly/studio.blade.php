@extends('layouts.learning')
@section('title', 'Weekly quest calendar — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('module-reviews.index') }}">← Review studio</a><h1>A fresh quest for every week.</h1>
<p>Sunday 00:00 to the next Sunday 00:00, Manila time. Only one week can be active. An active event's problem and rules stay fixed.</p>

@include('layouts.form-errors')
<form class="catalog-search" method="GET"><label for="week">Choose a Sunday</label><input id="week" type="date" name="week" value="{{ $week }}" required><label for="q">Search approved quests</label><input id="q" type="search" name="q" value="{{ $query }}" maxlength="80"><button class="button button-dark button-small">Open calendar week</button></form>
@if($event)<p class="lesson-note">{{ $event->revision->title }} · {{ $event->status }} · Version {{ $event->record_version }}</p>@endif
@if($editable)<p>Choose a quest below. Only its approved revision is included. New events use the approved capped reward policy; prizes require both earned issuance allowance and matured platform funding.</p>
@forelse($revisions as $revision)<form class="studio-form" method="POST" action="{{ route('weekly-studio.store') }}">@csrf
<h2>{{ $revision->title }}</h2><p>{{ config('challenges.languages')[$revision->language] }}</p>
<input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="challenge_id" value="{{ $revision->challenge_id }}"><input type="hidden" name="revision_id" value="{{ $revision->id }}"><input type="hidden" name="record_version" value="{{ $event?->record_version ?? 0 }}">
<label for="rules-{{ $revision->id }}">Weekly participation rules</label><textarea id="rules-{{ $revision->id }}" name="rules" rows="3" maxlength="20000" required>{{ $event?->rules ?? 'Solve this quest within the week. You have three attempts; standard quest attempts are separate.' }}</textarea>
<label><input type="checkbox" name="confirmed" value="1" required> I confirm this quest and weekly window.</label><button class="button button-play">{{ $event ? 'Replace scheduled quest' : 'Choose this quest' }} →</button></form>@empty<p>No approved Published quests match your search.</p>@endforelse{{ $revisions->links() }}
@else<p>This event has started or ended and is read-only.</p>@endif
<h2>Recent and upcoming weeks</h2>@forelse($upcoming as $scheduled)<p><a class="quiet-link" href="{{ route('weekly-studio.index', ['week' => $scheduled->starts_at->setTimezone('Asia/Manila')->toDateString()]) }}">{{ $scheduled->starts_at->setTimezone('Asia/Manila')->format('M j, Y') }} · {{ $scheduled->revision->title }} · {{ $scheduled->status }}</a>@if($scheduled->ends_at->isPast()) · <a class="quiet-link" href="{{ route('weekly-results.review', $scheduled) }}">Review final results</a>@endif</p>@empty<p>The calendar is waiting for its first quest.</p>@endforelse</section>
@endsection
