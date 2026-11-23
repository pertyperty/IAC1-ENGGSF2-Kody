@props(['title', 'url', 'action', 'icon'])
<article {{ $attributes->class('catalog-entry') }}>
    <div class="catalog-entry-heading">
        <span class="catalog-entry-icon" aria-hidden="true"><x-action-icon :name="$icon"/></span>
        <h2>{{ $title }}</h2>
    </div>
    @isset($meta)<div class="catalog-entry-meta">{{ $meta }}</div>@endisset
    <div class="catalog-entry-body">{{ $slot }}</div>
    <a class="catalog-entry-action quiet-link" href="{{ $url }}" aria-label="{{ $action }}: {{ $title }}">{{ $action }}<x-action-icon name="right"/></a>
</article>
