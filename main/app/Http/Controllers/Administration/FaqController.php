<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\DeleteFaqRequest;
use App\Http\Requests\Administration\SaveFaqRequest;
use App\Models\FaqEntry;
use App\Services\Administration\FaqManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FaqController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', FaqEntry::class);
        $entries = FaqEntry::where('status', 'Active')->orderByDesc('id')->paginate(20);

        return response()->view('help.manage-index', compact('entries'))->header('Cache-Control', 'no-store, private');
    }

    public function create(): Response
    {
        Gate::authorize('create', FaqEntry::class);

        return response()->view('help.editor', ['entry' => null, 'history' => null])->header('Cache-Control', 'no-store, private');
    }

    public function edit(FaqEntry $entry): Response
    {
        abort_unless($entry->status === 'Active', 404);
        Gate::authorize('update', $entry);
        $history = DB::table('audit_events')->where('subject_type', 'faq_entry')->where('subject_id', (string) $entry->id)->latest('created_at')->paginate(10);

        return response()->view('help.editor', compact('entry', 'history'))->header('Cache-Control', 'no-store, private');
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
