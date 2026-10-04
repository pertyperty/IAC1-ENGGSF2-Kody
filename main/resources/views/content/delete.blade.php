@extends('layouts.learning')
@section('title', 'Delete content — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route($studio.'.edit', $content) }}">← Back to your content</a><p class="overline">KEEP LEARNER JOURNEYS SAFE</p><h1>Delete this {{ $kind }}?</h1><p class="lesson-note">{{ $content->latestRevision->title }}</p>
@if($errors->any())<div class="studio-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($reasons)<p>This content has retained dependencies and cannot be permanently deleted:</p><ul>@foreach($reasons as $reason)<li>{{ $reason }}</li>@endforeach</ul><p>Learner and governance history stays protected.</p>@can('archive', $content)<a class="button button-dark" href="{{ route($studio.'.archive-confirmation', $content) }}">Archive instead →</a>@else<p>Archival is available only for Published content. This content and its history are preserved.</p>@endcan
@else<p>This permanently removes the content, its revisions and its own composition or test data. It cannot be undone. Deletion history remains in the audit log.</p><form method="POST" action="{{ route('content-deletion.store', [$kind, $content->id]) }}">@csrf<input type="hidden" name="record_version" value="{{ $content->record_version }}"><label><input type="checkbox" name="confirmed" value="1" required> I understand this permanently deletes my content.</label><p><button class="button button-dark">Permanently delete</button></p></form>@endif
<a class="quiet-link" href="{{ route($studio.'.edit', $content) }}">Cancel and keep content</a></section>
@endsection
