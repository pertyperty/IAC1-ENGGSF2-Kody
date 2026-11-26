@extends('layouts.learning')
@section('title', 'This week’s challenge — Kody')
@section('content')
<section class="page-width quest-page challenge-workspace">@include('weekly.navigation')<header class="quest-hero">@include('weekly.scenery')<div><p class="overline">THIS WEEK'S SHARED CHALLENGE</p><h1>{{ $revision->title }}</h1>
<p>Closes {{ $weeklyEvent->ends_at->setTimezone('Asia/Manila')->format('M j, Y · g:i a') }} Manila time. Three fresh attempts for this event.</p>
</div><x-kody-art size="140"/></header><a class="quiet-link challenge-workspace-jump" href="#solution">Go to editor</a><div class="quest-play-columns"><div data-quest-problem role="region" aria-label="Problem and examples" tabindex="0"><div class="lesson-note"><h2>The weekly rules</h2><p class="lesson-text">{{ $weeklyEvent->rules }}</p><p>Free play, three attempts and verified results. Your first passed attempt this week earns 25 bonus XP. @if($weeklyEvent->reward_mode === 'Capped')KodeBit prizes depend on the published results and the monthly reward budget.@else This event has no KodeBit prizes. @endif</p></div>
@include('challenges.problem')
@include('challenges.starter-code')
@include('challenges.samples')
</div><div class="challenge-solution-panel">@include('challenges.participation')@include('engagement.reactions')</div></div></section>
@endsection
