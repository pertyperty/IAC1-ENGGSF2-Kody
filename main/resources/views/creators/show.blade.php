@extends('layouts.learning')
@section('title', $creator->username.' — Creator profile — Kody')
@section('content')
<article class="review-page page-width creator-profile">
    <p class="overline">CREATOR PROFILE</p><h1>{{ $creator->username }}</h1>
    <x-creator-byline :creator="$creator" :linked="false"/>
    @if($creator->hasCreatorBadge())<p class="field-hint">Kody staff reviewed this creator's supporting qualifications. This is not a certification of every claim or lesson.</p>@endif
    @if($profile?->headline)<p><strong>{{ $profile->headline }}</strong></p>@endif
    @if(auth()->id() === $creator->id)<a class="button button-secondary" href="{{ route('creator-profile.edit') }}">Edit public profile</a>@endif
    @can('verifyCreator', $creator)<a class="button button-secondary" href="{{ route('creator-badges.show', $creator) }}">Review qualifications badge</a>@endcan
    @if($profile?->bio || $profile?->accomplishments)<section><h2>About this creator</h2><p class="field-hint">Provided by the creator.</p>
    @if($profile?->bio)<p>{{ $profile->bio }}</p>@endif
    @if($profile?->accomplishments)<h3>Experience and accomplishments</h3><p>{{ $profile->accomplishments }}</p>@endif</section>@endif
    @if(collect($content)->sum('total') === 0)<section><h2>Published work</h2><p>No published content yet.</p></section>@endif
    @foreach($content as $label => $group)
    @continue($group['total'] === 0)
    <section><h2>{{ $label }} <span class="field-hint">{{ $group['total'] }} published</span></h2>
    <ul class="review-list">@forelse($group['items'] as $item)<li><a href="{{ route(match($label) { 'Lessons' => 'modules.show', 'Courses' => 'course-learning.show', default => 'challenges.show' }, $item->id) }}">{{ $item->publishedRevision->title }}</a></li>@empty<li>No published content.</li>@endforelse</ul>
    @if($group['total'] > 6)<p class="field-hint">Showing the six most recent publications.</p>@endif</section>@endforeach
</article>
@endsection
