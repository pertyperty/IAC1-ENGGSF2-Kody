<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\ViewReportsRequest;
use App\Services\Administration\SystemReports;
use Illuminate\Http\Response;

class SystemReportController extends Controller
{
    public function __invoke(ViewReportsRequest $request, SystemReports $reports): Response
    {
        $filters = $request->validated();
        $request->session()->put('system_report_filters', $filters);
        $report = $reports->generate($request->user(), $request->session()->getId(), $filters);

        return response()->view('account.system-reports', compact('report', 'filters'))->header('Cache-Control', 'no-store, private');
    }
}
