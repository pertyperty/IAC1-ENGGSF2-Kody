@extends('layouts.learning')
@section('title', 'Write a helpful answer — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('faq-management.index') }}">← Help workshop</a><h1>{{ $entry ? 'Keep the answer useful.' : 'Clear up a little mystery.' }}</h1><p>Write a clear question and a friendly answer. Saving publishes it to public Help.</p>

@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="studio-form" method="POST" action="{{ $entry ? route('faq-management.update', $entry) : route('faq-management.store') }}">@csrf @if($entry)@method('PUT')@endif<input type="hidden" name="record_version" value="{{ $entry?->record_version ?? 1 }}">
<label for="category">Topic</label><select id="category" name="category">@foreach(config('help.categories') as $key => $label)<option value="{{ $key }}" @selected(old('category', $entry?->category) === $key)>{{ $label }}</option>@endforeach</select>
<label for="question">Question</label><input id="question" name="question" maxlength="255" value="{{ old('question', $entry?->question) }}" required>
<label for="answer">Answer</label><p class="field-hint">Plain text only. Include useful steps; formatting and code are shown as text.</p><textarea id="answer" name="answer" rows="8" maxlength="10000" required>{{ old('answer', $entry?->answer) }}</textarea><button class="button button-play">Save to Help →</button></form>
@if($entry)<p><a class="quiet-link" href="{{ route('help.show', $entry) }}">View the published answer</a></p><h2>Remove an outdated answer</h2><p>Deletion removes this entry from search and direct links. Audit history is retained.</p>
<form class="account-form" method="POST" action="{{ route('faq-management.delete', $entry) }}">@csrf<input type="hidden" name="record_version" value="{{ $entry->record_version }}"><label><input type="checkbox" name="confirmed" value="1" required> Confirm deletion of this FAQ</label><button class="button button-dark">Delete FAQ</button></form>@endif
@if($history)<h2>Change history</h2>@foreach($history as $action)<p>{{ $action->event }} · Administrator #{{ $action->actor_id }} · {{ \Carbon\CarbonImmutable::parse($action->created_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p>@endforeach{{ $history->links() }}@endif
</section>
@endsection
