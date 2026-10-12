<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><style>
@page { margin: 38px 40px; } body { font-family: DejaVu Sans, sans-serif; color: #192d47; font-size: 10px; line-height: 1.5; }
h1 { font-size: 23px; margin: 0 0 6px; } h2 { font-size: 14px; margin: 20px 0 8px; } .brand { color: #2457bc; font-weight: bold; font-size: 12px; letter-spacing: 2px; }
.meta { color: #58687c; margin-bottom: 18px; } table { border-collapse: collapse; width: 100%; } th,td { padding: 5px 9px; border-bottom: 1px solid #dae2ec; text-align: left; } thead { display: table-header-group; background: #edf3fc; } tr { page-break-inside: avoid; } td:last-child { text-align: right; } .note { background: #edf3fc; padding: 10px; } .footer { position: fixed; bottom: -20px; font-size: 8px; color: #58687c; }
</style></head><body><div class="brand">KODY / PLATFORM REPORT</div><h1>{{ ucfirst($report['filters']['type']) }} report</h1>
<div class="meta">{{ $report['filters']['from'] }} through {{ $report['filters']['to'] }} (Asia/Manila)<br>Generated {{ $report['generated_at']->format('M j, Y g:i A') }} Asia/Manila</div>
<p class="note">Counts reflect stored records within this window and their current state. Monetary metrics retain their labeled exact units. This report is not a tax statement or bank reconciliation.</p>
<h2>Insights</h2>@foreach($report['insights'] as $insight)<p><b>{{ $insight['title'] }}:</b> {{ $insight['detail'] }}</p>@endforeach
<h2>Recorded metrics</h2><table><thead><tr><th>Metric and unit</th><th>Total</th></tr></thead><tbody>@foreach($report['rows'] as $row)<tr><td>{{ $row['metric'] }}</td><td>{{ number_format($row['count']) }}</td></tr>@endforeach</tbody></table>
<div class="footer">Kody - private Administrator report. Retained records only.</div></body></html>
