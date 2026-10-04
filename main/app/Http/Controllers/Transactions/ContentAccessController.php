<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Services\Transactions\ContentAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContentAccessController extends Controller
{
    public function store(Request $request, string $kind, int $content, ContentAccess $access): RedirectResponse
    {
        $data = $request->validate(['revision_id' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']]);
        $access->unlock($request->user(), $request->session()->getId(), $kind, $content, (int) $data['revision_id']);

        return redirect()->route($kind === 'module' ? 'modules.show' : 'challenges.show', $content)->with('status', 'Access unlocked. Your KodeBit history is saved.');
    }
}
