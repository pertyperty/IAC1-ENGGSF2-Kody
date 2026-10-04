@extends('layouts.account')
@section('title', 'System reports — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
<h1>System reports</h1><p>Read-only totals from stored Kody records. Dates use Asia/Manila, including both selected calendar days.</p>
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="account-form" method="GET" action="{{ route('system-reports') }}">
<label>Report<select name="type">@foreach(['accounts' => 'Accounts', 'content' => 'Content', 'learning' => 'Validated learning', 'execution' => 'Coding attempts'] as $key => $label)<option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>@endforeach</select></label>
<label>From<input type="date" name="from" value="{{ $filters['from'] }}" required></label>
<label>Through<input type="date" name="to" value="{{ $filters['to'] }}" required></label>
<button class="primary-button">View report</button></form>
<p>Generated {{ $report['generated_at']->format('M j, Y g:i A') }} Asia/Manila · {{ $filters['from'] }} through {{ $filters['to'] }}.</p>
@if(in_array($filters['type'], ['accounts', 'content', 'execution'], true))<p>Counts select records by registration, creation or submission date and show their current state at this report snapshot. They are not historical state-change totals.</p>@else<p>Learning totals count stored validated completion records, enrollment records and completed course assignments. Replays on separate days and reuse across course assignments can appear separately; these are not distinct learners, distinct modules or rewards.</p>@endif
<table class="report-table"><caption>Report totals</caption><thead><tr><th scope="col">Metric</th><th scope="col">Count</th></tr></thead><tbody>@foreach($report['rows'] as $row)<tr><th scope="row">{{ $row['metric'] }}</th><td>{{ number_format($row['count']) }}</td></tr>@endforeach</tbody></table>
@if($filters['type'] === 'content')<p>Published counts can include staff-withdrawn content. These totals describe lifecycle, not learner availability.</p>@endif
<p>Financial, XP, rank and reward reports are unavailable while their supporting systems are deferred. Deleted private learning/submission records are excluded; minimal account tombstones and retained content remain where required.</p>
<p class="secondary-link"><a href="{{ route('account-governance.index') }}">Back to accounts</a></p>
@endsection
