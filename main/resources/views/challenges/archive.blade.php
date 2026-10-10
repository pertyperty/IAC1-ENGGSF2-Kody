@extends('layouts.learning')
@section('title', 'Archive a coding quest — Kody')
@section('content')
<section class="review-page page-width"><h1>Put this quest away?</h1><p>Archiving “{{ $challenge->publishedRevision->title }}” removes it from browsing and prevents new participation. All revisions, test cases and review history are preserved.</p>@include('layouts.form-errors')<form method="post" action="{{ route('challenges.archive', $challenge) }}" class="studio-form">@csrf<input type="hidden" name="record_version" value="{{ $challenge->record_version }}"><input type="hidden" name="confirmed" value="1"><div class="studio-actions"><button class="button button-dark">Confirm archive</button><a class="quiet-link" href="{{ route('challenges.edit', $challenge) }}">Keep this quest</a></div></form></section>
@endsection
