@props(['creator', 'linked' => true])
@if($creator && app(\App\Services\Account\CreatorProfiles::class)->visible($creator))
<span class="creator-byline">By
    @if($linked)<a href="{{ route('creators.show', $creator) }}">{{ $creator->username }}</a>@else<span>{{ $creator->username }}</span>@endif
    <span class="creator-role">{{ $creator->account_role->name }}</span>
    @if($creator->hasCreatorBadge())<span class="creator-badge" title="Qualifications reviewed by Kody staff"><x-action-icon name="check"/><span class="sr-only">Qualifications reviewed</span></span>@endif
</span>
@else<span class="creator-byline">Kody community content</span>@endif
