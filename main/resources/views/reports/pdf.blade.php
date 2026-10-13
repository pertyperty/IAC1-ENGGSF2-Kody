<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><style>
@page { margin: 42px 40px 48px; } body { font-family: DejaVu Sans, sans-serif; color: #20344d; font-size: 9px; line-height: 1.5; }
h1 { font-size: 25px; margin: 12px 0 6px; } h2 { font-size: 15px; margin: 18px 0 9px; } h3 { font-size: 11px; margin-bottom: 4px; }
.brand { color: #2457bc; font-weight: bold; font-size: 11px; letter-spacing: 2px; border-bottom: 3px solid #2457bc; padding-bottom: 12px; }
.meta { color: #53657c; margin-bottom: 16px; } .section { page-break-before: always; } .note { background: #eff4fa; padding: 12px; font-size: 8px; }
table { border-collapse: collapse; width: 100%; table-layout: fixed; } th,td { padding: 7px 8px; border-bottom: 1px solid #dae2ec; text-align: left; overflow-wrap: break-word; word-wrap: break-word; }
thead { display: table-header-group; background: #20344d; color: white; } tr { page-break-inside: avoid; } tbody tr:nth-child(even) { background: #f4f7fb; } .number { text-align: right; } .register { font-size: 7px; } .register th,.register td { padding: 7px 5px; } .id { font-size: 6px; }
.footer { position: fixed; bottom: -27px; font-size: 7px; color: #53657c; }
</style></head><body>
<div class="footer">CONFIDENTIAL / Kody management information / {{ $report['filters']['from'] }} - {{ $report['filters']['to'] }}</div>
<div class="brand">KODY / MANAGEMENT REPORT</div><h1>{{ ucfirst($report['filters']['type']) }} review</h1>
<div class="meta">Reporting period: {{ $report['filters']['from'] }} through {{ $report['filters']['to'] }}<br>Inclusive Asia/Manila dates | Generated {{ $report['generated_at']->format('M j, Y g:i A') }} Manila</div>
<p class="note">{{ \App\Services\Administration\ReportExports::SCOPE }}</p>
<h2>01 / Executive summary</h2>
@foreach($report['insights'] as $insight)<h3>{{ $insight['title'] }} / {{ $insight['value'] }}</h3><p>{{ $insight['detail'] }}</p>@endforeach
@if($report['financials'] !== null)
<div class="section"><div class="brand">KODY / FINANCIAL REVIEW</div><h2>02 / Cash movement and liabilities</h2>
<p class="note">{{ \App\Services\Administration\ReportExports::FINANCIAL_SCOPE }}</p>
<table><thead><tr><th style="width:68%">Financial measure</th><th class="number">PHP</th></tr></thead><tbody>@foreach($report['financials']['totals'] as $row)<tr><td>{{ $row['metric'] }}</td><td class="number">{{ \App\Support\ExactMoney::signedDecimal($row['minor']) }}</td></tr>@endforeach</tbody></table></div>
@endif
<div class="section"><div class="brand">KODY / OPERATING REVIEW</div><h2>Recorded operating metrics</h2><p class="meta">Current lifecycle states at the snapshot; dates select creation, submission or posting records. Counts do not reconstruct historical state changes.</p>
<table><thead><tr><th style="width:80%">Metric and unit</th><th class="number">Total</th></tr></thead><tbody>@foreach($report['rows'] as $row)<tr><td>{{ $row['metric'] }}</td><td class="number">{{ number_format($row['count']) }}</td></tr>@endforeach</tbody></table></div>
<div class="section"><div class="brand">KODY / PERIOD ANALYSIS</div><h2>{{ ucfirst($report['trends']['group']) }} activity trends</h2><p class="meta">First and last buckets may be partial. Completion records include valid replays. PHP columns show exact decimal amounts.</p>
<table class="register"><thead><tr><th style="width:17%">Period</th><th>New accounts</th><th>Assessed wins</th><th>Articles</th><th>Attempts</th><th>XP</th><th>Paid expenses PHP</th><th>Net cash PHP</th></tr></thead><tbody>@foreach($report['trends']['rows'] as $row)<tr><td>{{ $row['period'] }}</td><td>{{ $row['accounts'] }}</td><td>{{ $row['completions'] }}</td><td>{{ $row['articles'] }}</td><td>{{ $row['attempts'] }}</td><td>{{ $row['xp'] }}</td><td>{{ \App\Support\ExactMoney::signedDecimal($row['expenses_minor']) }}</td><td>{{ \App\Support\ExactMoney::signedDecimal($row['cash_minor']) }}</td></tr>@endforeach</tbody></table></div>
@if($report['financials'] !== null)
@foreach($report['financials']['registers'] as $register)
@if($register['count'] > 0)
<div class="section"><div class="brand">KODY / FINANCIAL APPENDIX</div><h2>{{ $register['title'] }}</h2><p class="meta">{{ min(40, count($register['rows'])) }} of {{ $register['count'] }} matching records. PDF contains the latest 40; Excel/CSV contain up to 200 per register. Narrow dates for additional records. Monthly estimates are listed by month and are separate from paid expenses.</p>
<table class="register"><thead><tr>@foreach($register['headers'] as $header)<th @if($loop->first) style="width:{{ count($register['headers']) > 3 ? '29' : '32' }}%" @endif>{{ $header }}</th>@endforeach</tr></thead><tbody>@forelse(array_slice($register['rows'], 0, 40) as $row)<tr>@foreach($row as $value)<td @class(['id' => $loop->first && strlen((string) $value) > 30])>{{ $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($register['headers']) }}">No retained records in this window.</td></tr>@endforelse</tbody></table></div>
@endif
@endforeach
@endif
</body></html>
