@extends('layouts.learning')
@section('title', 'Review staff withdrawal — Kody')
@section('content')
<section class="review-page page-width moderation-details"><h1>{{ $revision?->title ?? ucfirst($kind).' #'.$item->id }}</h1>
@if(session('status'))<p class="lesson-note" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="form-errors">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<p>{{ ucfirst($kind) }} #{{ $item->id }} · {{ $item->status }} · {{ $item->isWithdrawn() ? 'Staff withdrawal active' : 'No staff block' }}</p>
<p>{{ $revision?->description }}</p>
@if($kind === 'module')<pre class="lesson-note">{{ $revision?->content }}</pre><p>{{ $revision?->video_url }}</p><pre class="lesson-note">{{ json_encode($revision?->assessment, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
@elseif($kind === 'course')<h2>Pinned adventures</h2>@foreach($revision?->modules ?? [] as $slot)<p>{{ $slot->position }}. {{ $slot->revision->title }} · Module #{{ $slot->module_id }} · Revision #{{ $slot->module_revision_id }}</p>@endforeach
@elseif($kind === 'challenge')<p>{{ $revision?->language }} · {{ $revision?->rules }}</p><pre class="lesson-note">Input: {{ $revision?->input_format }}
Output: {{ $revision?->output_format }}</pre><h2>Approved revision test cases · Staff only</h2>@foreach($revision?->testCases ?? [] as $case)<pre class="lesson-note">{{ $case->hidden ? 'Hidden' : 'Sample' }} case {{ $case->position }}
Input: {{ $case->input }}
Expected: {{ $case->expected_output }}</pre>@endforeach
@endif
<p><a class="quiet-link" href="{{ match($kind) { 'module' => route('module-reviews.show', $item), 'course' => route('course-reviews.show', $item), 'challenge' => route('challenge-reviews.show', $item) } }}">Inspect revision details and existing publication review →</a></p>
<p>Withdrawal stops learner access, including enrolled course access and pinned lessons. Content, revisions, progress, audit and committed coding evaluations remain intact. Restoration removes only the staff block; existing lifecycle rules still apply.</p>
<form method="POST" action="{{ route('content-moderation.change', [$kind, $item->id]) }}" class="account-form">@csrf
<input type="hidden" name="record_version" value="{{ $item->record_version }}"><input type="hidden" name="action" value="{{ $item->isWithdrawn() ? 'Restored' : 'Withdrawn' }}">
@unless($item->isWithdrawn())<label><input type="checkbox" name="flagged" value="1" required> I reviewed this content and confirm it is flagged for staff moderation.</label>@endunless
<label><input type="checkbox" name="confirmed" value="1" required> I confirm this {{ $item->isWithdrawn() ? 'restoration' : 'withdrawal' }} decision.</label>
<button class="button button-dark">{{ $item->isWithdrawn() ? 'Restore from staff withdrawal' : 'Withdraw learner access' }}</button></form>
<h2>Staff moderation history</h2>@forelse($history as $entry)<p>{{ $entry->action }} · Staff #{{ $entry->actor_id }} · {{ $entry->lifecycle }} · {{ \Carbon\CarbonImmutable::parse($entry->created_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p>@empty<p>No recorded staff withdrawal actions.</p>@endforelse {{ $history->links() }}
<a class="quiet-link" href="{{ route('content-moderation.index', ['kind' => $kind]) }}">Back to staff content moderation</a></section>
@endsection
