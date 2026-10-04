@extends('layouts.learning')
@section('title', 'Weekly results — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('leaderboards.index') }}">← Leaderboards</a><p class="overline">VERIFIED WEEKLY SCORES</p><h1>Week of {{ $week->starts_at->setTimezone('Asia/Manila')->format('M j, Y') }}</h1><p>{{ $set->status }} · Best terminal evaluation per player. Scores determine shared positions; speed and spending never break ties.</p>
@if($errors->any())<p class="studio-errors" role="alert">{{ $errors->first() }}</p>@endif
<div class="review-list">@forelse($results as $result)<div class="lesson-note"><b>#{{ $result->rank }} · {{ $result->account_status === 'Active' ? ($result->username ?? 'Kody player') : 'Former player' }}</b><p>{{ intdiv($result->score, 100) }}.{{ str_pad((string)($result->score % 100), 2, '0', STR_PAD_LEFT) }}% · {{ $result->reward_kb }} KB confirmed</p></div>@empty<p>No eligible terminal evaluations were recorded for this week.</p>@endforelse</div>{{ $results->links() }}
@if($preview && $set->status === 'Draft')<p class="lesson-note">This preview grants no rewards. Publication fixes scores permanently; rewards may be zero when the 4% allowance or matured cash cannot fund the prize pool.</p><form method="post" action="{{ route('weekly-results.publish', $week) }}">@csrf<input type="hidden" name="record_version" value="{{ $week->record_version }}"><label class="checkbox-label"><input name="confirmed" type="checkbox" value="1" required> Publish these immutable results and their funded rewards.</label><button class="button button-play">Publish final results</button></form>@endif
</section>
@endsection
