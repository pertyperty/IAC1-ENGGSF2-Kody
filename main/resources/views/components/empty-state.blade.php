@props(['title', 'description', 'actionUrl' => null, 'actionLabel' => null])
<section class="empty-state catalog-empty">
    <h2>{{ $title }}</h2>
    <p>{{ $description }}</p>
    @if($actionUrl && $actionLabel)<a class="button button-secondary" href="{{ $actionUrl }}">{{ $actionLabel }}</a>@endif
</section>
