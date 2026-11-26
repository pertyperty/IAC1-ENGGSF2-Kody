@extends('layouts.learning')
@section('title', $creator->username.' — Creator profile — Kody')
@section('content')
@php
    $isInstructor = $creator->account_role === \App\Enums\Role::Instructor;
    $isOwner = auth()->id() === $creator->id;
    $totalPublished = array_sum($totals);
    $roleKey = strtolower($creator->account_role->value);
@endphp
<article class="review-page page-width creator-profile">
    <header class="creator-hero creator-hero--{{ $roleKey }}">
        <div class="creator-hero-identity">
            @auth
                @include('layouts.avatar', ['avatarUser' => $creator, 'avatarClass' => 'profile-avatar creator-hero-avatar'])
            @else
                <span class="player-avatar profile-avatar creator-hero-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($creator->username ?? 'K', 0, 1)) }}</span>
            @endauth
            <div class="creator-hero-copy">
                <p class="overline">{{ strtoupper($creator->account_role->name) }} PROFILE</p>
                <h1>{{ $creator->username }}</h1>
                <x-creator-byline :creator="$creator" :linked="false"/>
                @if($profile?->headline)<p class="creator-headline">{{ $profile->headline }}</p>@endif
                @if($creator->hasCreatorBadge())<p class="field-hint">Kody staff reviewed this creator’s supporting qualifications. This is not a certification of every claim or lesson.</p>@endif
            </div>
        </div>
        <dl class="creator-stat-strip" aria-label="Published counts">
            <div><dt>Modules</dt><dd>{{ number_format($totals['modules']) }}</dd></div>
            <div><dt>Courses</dt><dd>{{ number_format($totals['courses']) }}</dd></div>
            <div><dt>Challenges</dt><dd>{{ number_format($totals['challenges']) }}</dd></div>
        </dl>
        @if($socials !== [])
            <nav class="creator-socials" aria-label="Public links">
                @foreach($socials as $key => $social)
                    <a class="creator-social-link" href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"><x-action-icon name="link"/><span>{{ $social['label'] }}</span></a>
                @endforeach
            </nav>
        @endif
        @if($isOwner || auth()->user()?->can('verifyCreator', $creator))
            <div class="studio-actions creator-hero-actions">
                @if($isOwner)<a class="button button-secondary" href="{{ route('creator-profile.edit') }}">Edit public profile</a>@endif
                @can('verifyCreator', $creator)<a class="button button-secondary" href="{{ route('creator-badges.show', $creator) }}">Review qualifications badge</a>@endcan
            </div>
        @endif
    </header>

    @if($isInstructor || $profile?->qualifications || $profile?->bio || $profile?->accomplishments || $socials !== [])
        <div class="creator-info-grid">
            @if($isInstructor || $profile?->qualifications || $creator->hasCreatorBadge())
                <section class="creator-info-card">
                    <h2>Qualifications</h2>
                    @if($creator->hasCreatorBadge())
                        <p class="creator-verified-note"><x-action-icon name="check"/> Supporting credentials were reviewed by Kody staff.</p>
                    @endif
                    @if($profile?->qualifications)
                        <p class="creator-prose">{{ $profile->qualifications }}</p>
                    @else
                        <p class="field-hint">{{ $isOwner ? 'Add a public summary of your degrees, certifications, or teaching credentials so learners can see your background.' : 'No public qualification summary provided yet.' }}</p>
                    @endif
                </section>
            @endif

            @if($isInstructor || $profile?->bio || $profile?->accomplishments)
                <section class="creator-info-card">
                    <h2>Bio and experience</h2>
                    @if($profile?->bio || $profile?->accomplishments)
                        <p class="field-hint">Provided by the creator.</p>
                        @if($profile?->bio)<p class="creator-prose">{{ $profile->bio }}</p>@endif
                        @if($profile?->accomplishments)
                            <h3>Experience and accomplishments</h3>
                            <p class="creator-prose">{{ $profile->accomplishments }}</p>
                        @endif
                    @else
                        <p class="field-hint">{{ $isOwner ? 'Share a short bio and your teaching experience from your public profile editor.' : 'No bio provided yet.' }}</p>
                    @endif
                </section>
            @endif

            @if($isInstructor && $socials === [])
                <section class="creator-info-card">
                    <h2>Socials and links</h2>
                    <p class="field-hint">{{ $isOwner ? 'Add your website, GitHub, LinkedIn, or X profile so learners can explore more of your work.' : 'No public social links added yet.' }}</p>
                </section>
            @endif
        </div>
    @endif

    <section class="creator-work-section" aria-labelledby="creator-work-heading">
        <div class="section-heading creator-work-header">
            <div>
                <h2 id="creator-work-heading">Published work</h2>
                <p class="field-hint">{{ number_format($totalPublished) }} {{ $totalPublished === 1 ? 'published item' : 'published items' }} across Kody.</p>
            </div>
        </div>

        @if($totalPublished > 0)
            <div class="creator-work-toolbar">
                <nav class="studio-wayfinding creator-work-tabs" aria-label="Published work categories">
                    <a href="{{ route('creators.show', array_filter(['creator' => $creator, 'q' => $search !== '' ? $search : null])) }}" @if($activeTab === 'all') aria-current="page" @endif>All ({{ number_format($totalPublished) }})</a>
                    @if($totals['modules'] > 0)<a href="{{ route('creators.show', array_filter(['creator' => $creator, 'tab' => 'modules', 'q' => $search !== '' ? $search : null])) }}" @if($activeTab === 'modules') aria-current="page" @endif>Modules ({{ number_format($totals['modules']) }})</a>@endif
                    @if($totals['courses'] > 0)<a href="{{ route('creators.show', array_filter(['creator' => $creator, 'tab' => 'courses', 'q' => $search !== '' ? $search : null])) }}" @if($activeTab === 'courses') aria-current="page" @endif>Courses ({{ number_format($totals['courses']) }})</a>@endif
                    @if($totals['challenges'] > 0)<a href="{{ route('creators.show', array_filter(['creator' => $creator, 'tab' => 'challenges', 'q' => $search !== '' ? $search : null])) }}" @if($activeTab === 'challenges') aria-current="page" @endif>Challenges ({{ number_format($totals['challenges']) }})</a>@endif
                </nav>
                <form method="get" action="{{ route('creators.show', $creator) }}" class="creator-work-search">
                    @if($activeTab !== 'all')<input type="hidden" name="tab" value="{{ $activeTab }}">@endif
                    <label for="creator-work-q" class="sr-only">Search published work</label>
                    <input id="creator-work-q" type="search" name="q" maxlength="80" value="{{ $search }}" placeholder="Search titles">
                    <button type="submit" class="button button-secondary button-small">Search</button>
                    @if($search !== '')<a class="quiet-link" href="{{ route('creators.show', array_filter(['creator' => $creator, 'tab' => $activeTab !== 'all' ? $activeTab : null])) }}">Clear</a>@endif
                </form>
            </div>
        @endif

        @if($totalPublished === 0)
            <x-empty-state title="No published work yet" description="Approved published modules, courses, and challenges from this creator will appear here."/>
        @else
            @php
                $renderedAny = false;
            @endphp
            @foreach($content as $label => $group)
                @continue($activeTab !== 'all' && $activeTab !== $group['tab'])
                @continue($group['total'] === 0 && $activeTab === 'all')
                @php
                    $renderedAny = true;
                @endphp
                <section class="creator-work-group">
                    <div class="panel-heading">
                        <h3>{{ $label }} <span class="field-hint">{{ number_format($group['total']) }} published</span></h3>
                        @if($activeTab === 'all' && $group['total'] > 6)
                            <a class="quiet-link" href="{{ route('creators.show', array_filter(['creator' => $creator, 'tab' => $group['tab'], 'q' => $search !== '' ? $search : null])) }}">View all {{ number_format($group['total']) }} <x-action-icon name="right"/></a>
                        @endif
                    </div>
                    @if($group['items']->isEmpty())
                        <x-empty-state title="No matching {{ strtolower($label) }}" description="Try another search term or view all published work."/>
                    @else
                        <div class="creator-work-grid">
                            @foreach($group['items'] as $item)
                                @php
                                    $itemUrl = route(match ($label) {
                                        'Modules' => 'modules.show',
                                        'Courses' => 'course-learning.show',
                                        default => 'challenges.show',
                                    }, $item->id);
                                    $itemIcon = match ($label) {
                                        'Modules' => 'book',
                                        'Courses' => 'journey',
                                        default => 'code',
                                    };
                                    $itemAction = match ($label) {
                                        'Modules' => 'Open module',
                                        'Courses' => 'View course',
                                        default => 'View challenge',
                                    };
                                @endphp
                                <article class="creator-work-card">
                                    <div class="creator-work-card-head">
                                        <span class="catalog-entry-icon" aria-hidden="true"><x-action-icon :name="$itemIcon"/></span>
                                        <span class="creator-work-kind">{{ rtrim($label, 's') }}</span>
                                    </div>
                                    <h4><a href="{{ $itemUrl }}">{{ $item->publishedRevision->title }}</a></h4>
                                    <a class="catalog-entry-action quiet-link" href="{{ $itemUrl }}">{{ $itemAction }}<x-action-icon name="right"/></a>
                                </article>
                            @endforeach
                        </div>
                        @if($activeTab === $group['tab'])
                            {{ $group['paginator']->links() }}
                        @endif
                    @endif
                </section>
            @endforeach
            @if(!$renderedAny)
                <x-empty-state title="No matching publications" description="Try another search term or clear your filter." :action-url="route('creators.show', $creator)" action-label="Clear search"/>
            @endif
        @endif
    </section>
</article>
@endsection
