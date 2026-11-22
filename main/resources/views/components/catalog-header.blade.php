@props(['title', 'description'])
<header class="catalog-header">
    <div>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
    </div>
    @if($slot->isNotEmpty())<nav class="catalog-links" aria-label="Related learning pages">{{ $slot }}</nav>@endif
</header>
