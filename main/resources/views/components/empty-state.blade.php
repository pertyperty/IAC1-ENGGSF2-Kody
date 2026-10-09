@props(['title', 'description', 'actionUrl', 'actionLabel'])
<section class="empty-state catalog-empty">
    <h2>{{ $title }}</h2>
    <p>{{ $description }}</p>
    <a class="button button-secondary" href="{{ $actionUrl }}">{{ $actionLabel }}</a>
</section>
