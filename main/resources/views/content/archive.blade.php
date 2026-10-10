@extends('layouts.learning')
@section('title', 'Archive an adventure — Kody')
@section('content')
<section class="review-page page-width"><h1>Put this adventure away?</h1><p>Archiving “{{ $module->publishedRevision->title }}” removes it from Learning and blocks learner access. Your saved revisions, learner activity history and review records are preserved.</p>
    @include('layouts.form-errors')
    <form method="post" action="{{ route('studio.archive', $module) }}" class="studio-form">@csrf<input type="hidden" name="record_version" value="{{ $module->record_version }}"><input type="hidden" name="confirmed" value="1"><div class="studio-actions"><button class="button button-dark">Confirm archive</button><a class="quiet-link" href="{{ route('studio.edit', $module) }}">Keep this adventure</a></div></form>
</section>
@endsection
