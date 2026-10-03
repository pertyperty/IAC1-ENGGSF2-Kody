<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\DeleteOwnedContentRequest;
use App\Services\Challenges\ChallengeDeletion;
use App\Services\Content\ContentDeletion;
use App\Services\Publishing\OwnedContentDeletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContentDeletionController extends Controller
{
    public function show(Request $request, string $kind, int $content): Response
    {
        $data = $this->service($kind)->confirmation($request->user(), $request->session()->getId(), $kind, $content);
        $data['studio'] = $this->studio($kind);

        return response()->view('content.delete', $data)->header('Cache-Control', 'no-store, private');
    }

    public function store(DeleteOwnedContentRequest $request, string $kind, int $content): RedirectResponse
    {
        $this->service($kind)->delete($request->user(), $request->session()->getId(), $kind, $content,
            (int) $request->validated('record_version'), $request->boolean('confirmed'));

        return redirect()->route($this->studio($kind).'.index')->with('status', 'Content permanently deleted.');
    }

    private function service(string $kind): OwnedContentDeletion
    {
        return app($kind === 'challenge' ? ChallengeDeletion::class : ContentDeletion::class);
    }

    private function studio(string $kind): string
    {
        return match ($kind) {
            'module' => 'studio', 'course' => 'courses', 'challenge' => 'challenges', default => abort(404)
        };
    }
}
