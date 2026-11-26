@props(['creator', 'linked' => true])
@if($creator && app(\App\Services\Account\CreatorProfiles::class)->visible($creator))
<span class="creator-byline">By
    @if($linked)<a class="creator-byline-name" href="{{ route('creators.show', $creator) }}">{{ $creator->username }}</a>@else<span class="creator-byline-name">{{ $creator->username }}</span>@endif
    <x-role-badge :user="$creator" :show-verified="true"/>
</span>
@else<span class="creator-byline">Kody community content</span>@endif
