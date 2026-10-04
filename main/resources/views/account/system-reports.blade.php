@extends('layouts.account')
@section('title', 'System reports — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
<h1>System reports</h1><p>Read-only totals from stored Kody records. Dates use Asia/Manila, including both selected calendar days.</p>
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="account-form" method="GET" action="{{ route('system-reports') }}">
<label>Report<select name="type">@foreach(['accounts' => 'Accounts', 'content' => 'Content', 'learning' => 'Validated learning', 'execution' => 'Coding attempts', 'economy' => 'Posted accounting', 'rewards' => 'Validated XP and rewards'] as $key => $label)<option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>@endforeach</select></label>
<label>From<input type="date" name="from" value="{{ $filters['from'] }}" required></label>
<label>Through<input type="date" name="to" value="{{ $filters['to'] }}" required></label>
<button class="primary-button">View report</button></form>
<p>Generated {{ $report['generated_at']->format('M j, Y g:i A') }} Asia/Manila · {{ $filters['from'] }} through {{ $filters['to'] }}.</p>
@if(in_array($filters['type'], ['accounts', 'content', 'execution'], true))<p>Counts select records by registration, creation or submission date and show their current state at this report snapshot. They are not historical state-change totals.</p>@elseif($filters['type'] === 'learning')<p>Learning totals count stored validated completion records, enrollment records and completed course assignments. Replays on separate days and reuse across course assignments can appear separately; these are not distinct learners, distinct modules or rewards.</p>@endif
@if(in_array($filters['type'], ['economy', 'rewards'], true))<p>These totals use the immutable ledger posting or grant date within the selected window. Amounts labeled PHP centavos use exact minor units (100 centavos = PHP 1). They are not gross payment revenue, provider bank reconciliation or tax statements. Refunds and reversals retain their original postings and appear in later ledger history.</p>@endif
<table class="report-table"><caption>Report totals</caption><thead><tr><th scope="col">Metric and unit</th><th scope="col">Total</th></tr></thead><tbody>@foreach($report['rows'] as $row)<tr><th scope="row">{{ $row['metric'] }}</th><td>{{ number_format($row['count']) }}</td></tr>@endforeach</tbody></table>
@if($filters['type'] === 'content')<p>Published counts can include staff-withdrawn content. These totals describe lifecycle, not learner availability.</p>@endif
<p>Financial review and accounting totals are available in Finance; XP standings and published weekly rewards are available in Leaderboards. Deleted private learning/submission records are excluded; minimal account tombstones and retained content remain where required.</p>
<p><a href="{{ route('finance.index') }}">Finance and retained ledger history</a> · <a href="{{ route('leaderboards.index') }}">Learning standings</a></p>
<p class="secondary-link"><a href="{{ route('account-governance.index') }}">Back to accounts</a></p>
@endsection
