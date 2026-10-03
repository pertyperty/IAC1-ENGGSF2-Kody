<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\ModerateContentRequest;
use App\Models\LearningModule;
use App\Services\Administration\ContentModeration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ContentModerationController extends Controller
{
    public function index(Request $request, ContentModeration $moderation): Response
    {
        Gate::authorize('viewAny', LearningModule::class);
        $filters = $request->validate(['kind' => ['nullable', Rule::in(['module', 'course', 'challenge'])]]);
        $kind = $filters['kind'] ?? 'module';
        $class = $moderation->model($kind);
        $items = $class::whereIn('status', ['Published', 'Archived'])->with('publishedRevision')->orderByDesc('id')->paginate(20)->appends(['kind' => $kind]);

        return response()->view('content.moderation-index', compact('kind', 'items'))->header('Cache-Control', 'no-store, private');
    }

    public function show(string $kind, int $content, ContentModeration $moderation): Response
    {
        $class = $moderation->model($kind);
        $item = $class::findOrFail($content);
        Gate::authorize('moderate', $item);
        $revision = $item->publishedRevision;
        if ($kind === 'course') {
            $revision?->load('modules.revision');
        } elseif ($kind === 'challenge') {
            $revision?->load('testCases');
        }
        $history = DB::table('content_moderation_actions')->where($kind.'_id', $item->id)->latest('created_at')->paginate(10);

        return response()->view('content.moderation', compact('kind', 'item', 'revision', 'history'))->header('Cache-Control', 'no-store, private');
    }

    public function change(ModerateContentRequest $request, string $kind, int $content, ContentModeration $moderation): RedirectResponse
    {
        $moderation->change($request->user(), $request->session()->getId(), $kind, $content, $request->validated());

        return redirect()->route('content-moderation.show', [$kind, $content])->with('status', 'Staff moderation action saved.');
    }
}
