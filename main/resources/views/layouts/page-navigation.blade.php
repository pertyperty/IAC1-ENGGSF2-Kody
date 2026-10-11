@if(!request()->routeIs('home', 'dashboard'))
<nav class="page-navigation" aria-label="Page navigation">
    <a class="button button-secondary button-small" href="{{ $chrome['back']['url'] }}">← {{ $chrome['back']['label'] }}</a>
    @auth<a class="button button-quiet button-small" href="{{ route('home') }}" aria-label="Home" title="Home"><x-action-icon name="home" /></a>@endauth
</nav>
@endif
