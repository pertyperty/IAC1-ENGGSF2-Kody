<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\ViewReportsRequest;
use App\Services\Administration\ReportExports;
use App\Services\Administration\SystemReports;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

class SystemReportController extends Controller
{
    public function __invoke(ViewReportsRequest $request, SystemReports $reports): Response
    {
        $format = $request->validated('format');
        $filters = $request->safe()->except('format');
        $request->session()->put('system_report_filters', $filters);
        $report = $reports->generate($request->user(), $request->session()->getId(), $filters);
        if ($format !== 'html') {
            return app(ReportExports::class)->download($report, $format);
        }

        $today = CarbonImmutable::now('Asia/Manila');
        $presets = [
            'Today' => ['from' => $today->toDateString(), 'to' => $today->toDateString(), 'group' => 'day'],
            'This month' => ['from' => $today->startOfMonth()->toDateString(), 'to' => $today->toDateString(), 'group' => 'day'],
            'This year' => ['from' => $today->startOfYear()->toDateString(), 'to' => $today->toDateString(), 'group' => 'month'],
            'Last year' => ['from' => $today->subYear()->startOfYear()->toDateString(), 'to' => $today->subYear()->endOfYear()->toDateString(), 'group' => 'month'],
        ];

        return response()->view('account.system-reports', compact('report', 'filters', 'presets'))->header('Cache-Control', 'no-store, private');
    }
}
