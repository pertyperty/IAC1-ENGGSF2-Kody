@include('layouts.form-errors')
<form class="studio-form" method="post" action="{{ $editorEntry ? route('faq-management.update', $editorEntry) : route('faq-management.store') }}">@csrf
@if($editorEntry) @method('PUT') @endif
<input type="hidden" name="record_version" value="{{ $editorEntry?->record_version ?? 1 }}">
<label for="faq-category">Topic</label><select id="faq-category" name="category">@foreach(config('help.categories') as $key => $label)<option value="{{ $key }}" @selected(old('category', $editorEntry?->category) === $key)>{{ $label }}</option>@endforeach</select>
<label for="faq-question">Question</label><input id="faq-question" name="question" maxlength="255" value="{{ old('question', $editorEntry?->question) }}" required>
<label for="faq-answer">Answer</label><textarea id="faq-answer" name="answer" rows="10" maxlength="10000" required>{{ old('answer', $editorEntry?->answer) }}</textarea>
<p class="field-hint">Plain text. Saving publishes this answer to Help.</p>
<button class="button button-play">Save to Help</button>
</form>
@if($editorEntry)<details class="faq-secondary"><summary>Delete or view history</summary><p>Deleting removes this FAQ from Help. Its audit history stays saved.</p><form class="account-form" method="post" action="{{ route('faq-management.delete', $editorEntry) }}">@csrf<input type="hidden" name="record_version" value="{{ $editorEntry->record_version }}"><label><input type="checkbox" name="confirmed" value="1" required> Confirm deletion of this FAQ</label><button class="button button-dark">Delete FAQ</button></form>
@if($history)<h3>Change history</h3>@foreach($history as $action)<p>{{ $action->event }} - {{ \Carbon\CarbonImmutable::parse($action->created_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }}</p>@endforeach{{ $history->links() }}@endif
</details>@endif
