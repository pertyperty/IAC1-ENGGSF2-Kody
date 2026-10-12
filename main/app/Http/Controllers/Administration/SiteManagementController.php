<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdateSiteAnnouncementRequest;
use App\Services\Administration\SiteManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SiteManagementController extends Controller
{
    public function index(Request $request, SiteManagement $site): Response
    {
        return response()->view('account.site-management', $site->snapshot($request->user(), $request->session()->getId()))->header('Cache-Control', 'no-store, private');
    }

    public function update(UpdateSiteAnnouncementRequest $request, SiteManagement $site): RedirectResponse
    {
        $site->save($request->user(), $request->session()->getId(), $request->validated());

        return redirect()->route('site-management.index')->with('status', 'Site announcement saved. Its previous version is retained in the audit trail.');
    }
}
