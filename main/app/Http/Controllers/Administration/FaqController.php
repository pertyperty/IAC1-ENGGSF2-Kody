<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HelpController;
use App\Http\Requests\Administration\DeleteFaqRequest;
use App\Http\Requests\Administration\SaveFaqRequest;
use App\Models\FaqEntry;
use App\Services\Administration\FaqManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FaqController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FaqEntry::class);

        return app(HelpController::class)->index($request);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', FaqEntry::class);
        $request->merge(['new' => true]);

        return app(HelpController::class)->index($request);
    }

    public function edit(Request $request, FaqEntry $entry): Response
    {
        abort_unless($entry->status === 'Active', 404);
        Gate::authorize('update', $entry);
        $request->merge(['edit' => $entry->id]);

        return app(HelpController::class)->index($request);
    }

    public function store(SaveFaqRequest $request, FaqManagement $faqs): RedirectResponse
    {
        $entry = $faqs->save($request->user(), $request->session()->getId(), $request->validated());

        return redirect()->route('faq-management.edit', $entry)->with('status', 'FAQ published to Help.');
    }

    public function update(SaveFaqRequest $request, FaqEntry $entry, FaqManagement $faqs): RedirectResponse
    {
        $faqs->save($request->user(), $request->session()->getId(), $request->validated(), $entry);

        return redirect()->route('faq-management.edit', $entry)->with('status', 'FAQ updated. Help now shows the saved answer.');
    }

    public function delete(DeleteFaqRequest $request, FaqEntry $entry, FaqManagement $faqs): RedirectResponse
    {
        $faqs->delete($request->user(), $request->session()->getId(), $entry, (int) $request->validated('record_version'), true);

        return redirect()->route('faq-management.index')->with('status', 'FAQ deleted from Help. Audit history is retained.');
    }
}
