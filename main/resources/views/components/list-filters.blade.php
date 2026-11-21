@props(['filters' => [], 'statuses' => [], 'searchLabel' => 'Search titles'])
<form method="get" action="{{ url()->current() }}" class="catalog-search catalog-filter-panel">
    <div class="filter-grid"><label>{{ $searchLabel }}<input type="search" name="q" maxlength="80" value="{{ $filters['q'] ?? '' }}" placeholder="Enter a search term"></label>
    @if($statuses)<label>Status<select name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></label>@endif</div>
    <div class="filter-actions"><button class="button button-dark button-small">Apply filters</button>@if(array_filter($filters))<a class="quiet-link" href="{{ url()->current() }}">Clear filters</a>@endif</div>
</form>
