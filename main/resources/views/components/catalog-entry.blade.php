@props(['title', 'url', 'action', 'icon', 'tags' => null, 'maxTags' => 3, 'bookmarked' => false, 'bookmarkKind' => null, 'bookmarkContent' => null])
@php
    $cleanTags = is_array($tags) ? array_values(array_filter(array_map(fn ($tag) => is_string($tag) ? trim($tag) : '', $tags), fn ($tag) => $tag !== '')) : null;
    $visibleTags = $cleanTags !== null ? array_slice($cleanTags, 0, max(1, (int) $maxTags)) : [];
    $hiddenTags = $cleanTags !== null ? array_slice($cleanTags, max(1, (int) $maxTags)) : [];
@endphp
<article {{ $attributes->class(['catalog-entry', 'is-bookmarked' => $bookmarked]) }}>
    <div class="catalog-entry-heading">
        <span class="catalog-entry-icon" aria-hidden="true"><x-action-icon :name="$icon"/></span>
        <h2>{{ $title }}</h2>
        @if($bookmarkKind && $bookmarkContent)
            <x-bookmark :kind="$bookmarkKind" :content="$bookmarkContent" :bookmarked="$bookmarked" :compact="true" :label="$title"/>
        @endif
    </div>
    @if($cleanTags !== null)
        <div class="catalog-entry-meta" aria-label="Details">
            @foreach($visibleTags as $tag)<span class="catalog-tag">{{ $tag }}</span>@endforeach
            @if($hiddenTags !== [])
                <span class="catalog-tag catalog-tag-more" title="{{ implode(' · ', $hiddenTags) }} — open to view all">+{{ count($hiddenTags) }} more</span>
            @endif
        </div>
    @elseif(isset($meta))
        <div class="catalog-entry-meta">{{ $meta }}</div>
    @endif
    <div class="catalog-entry-body">{{ $slot }}</div>
    <a class="catalog-entry-action quiet-link" href="{{ $url }}" aria-label="{{ $action }}: {{ $title }}">{{ $action }}<x-action-icon name="right"/></a>
</article>
