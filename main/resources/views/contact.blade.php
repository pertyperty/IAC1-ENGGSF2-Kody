@extends('layouts.learning')
@section('title', 'Contact Kody — Support and account appeals')
@section('content')
<section class="contact-page page-width">
@php
    $reportedLesson = request()->query('lesson');
    $reportedRevision = filter_var(request()->query('revision'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    $lessonContext = is_string($reportedLesson) && preg_match('/\ATower level [1-9][0-9]{0,3}\z/D', $reportedLesson) && $reportedRevision;
@endphp
@if($lessonContext)
<aside class="content-panel"><h2>Report a lesson issue</h2><p>{{ $reportedLesson }} · revision {{ $reportedRevision }}. Explain the instruction or result that seemed wrong. This reference is included only in the email subject; no private attempt is attached.</p><a class="button button-secondary" href="mailto:{{ config('support.email') }}?subject={{ rawurlencode('Kody lesson issue: '.$reportedLesson.', revision '.$reportedRevision) }}"><x-action-icon name="mail"/> Email lesson feedback</a></aside>
@endif
<div class="support-hero"><div><p class="overline">A LITTLE HELP FROM A HUMAN</p><h1>Let’s get you unstuck</h1><p>Questions about your account, learning or creating? Reach the Kody support team using your own email app.</p><a class="button button-play" href="mailto:{{ config('support.email') }}"><x-action-icon name="mail"/> {{ config('support.email') }}</a><p class="field-hint">No email app configured? Copy the address into Gmail or your preferred email service.</p></div><x-kody-art size="160"/></div>
<div class="support-topics"><article class="content-panel" id="appeals"><x-action-icon name="shield"/><h2>Think your suspension was a mistake?</h2><p>Ask for a review. Include your username, the email you registered with and a brief explanation of what happened. An appeal does not automatically restore access; staff will review the account’s history.</p><a class="button button-secondary" href="mailto:{{ config('support.email') }}?subject={{ rawurlencode('Kody account appeal') }}"><x-action-icon name="mail"/> Email an appeal</a></article><article class="content-panel"><x-action-icon name="help"/><h2>Something isn’t working?</h2><p>Tell us the page, what you tried and the result you expected. Browser/device details and a screenshot with personal information hidden can help.</p><a class="button button-secondary" href="mailto:{{ config('support.email') }}?subject={{ rawurlencode('Kody support request') }}"><x-action-icon name="mail"/> Ask for support</a></article><article class="content-panel"><x-action-icon name="create"/><h2>Creating an adventure?</h2><p>Start with the Help center for publishing, reviews and game templates. For a question about your own draft, include its title or page link.</p><a class="button button-secondary" href="{{ route('help.index', ['category' => 'creating-content']) }}"><x-action-icon name="book"/> Creator guides</a></article></div>
<p class="support-privacy"><x-action-icon name="lock"/> Keep passwords, verification codes, API keys and full payment details out of email. This page opens your email app; Kody does not send or store an appeal here.</p>
</section>
@endsection
