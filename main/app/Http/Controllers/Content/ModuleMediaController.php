<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\LearningModule;
use App\Models\ModuleRevision;
use App\Services\Content\CourseLearning;
use App\Services\Transactions\ContentAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModuleMediaController extends Controller
{
    public function show(Request $request, LearningModule $module, int $revision, int $asset): Response|StreamedResponse
    {
        $data = $request->validate(['course' => ['nullable', 'integer', 'min:1'], 'slot' => ['required_with:course', 'integer', 'min:1'], 'download' => ['sometimes', 'boolean'], 'inline' => ['sometimes', 'boolean']]);
        $snapshot = ModuleRevision::where('module_id', $module->id)->findOrFail($revision);
        if (Gate::allows('viewOwned', $module) || Gate::allows('viewAny', LearningModule::class)) {
            // Owners and reviewers can inspect all preserved media versions.
        } elseif (isset($data['course'])) {
            $lesson = app(CourseLearning::class)->lesson($request->user(), $request->session()->getId(), $data['course'], $data['slot']);
            abort_unless($lesson['slot']->module_id === $module->id && $lesson['revision']->id === $snapshot->id, 404);
        } else {
            $access = app(ContentAccess::class)->open($request->user(), $request->session()->getId(), 'module', $module->id);
            abort_unless($access['accessible'] && $access['revision']->id === $snapshot->id, 403);
        }
        $attachment = $snapshot->attachments[$asset] ?? abort(404);
        $disk = Storage::disk($attachment['disk']);
        abort_unless($disk->exists($attachment['path']), 404, 'This attachment is temporarily unavailable.');
        $params = array_filter(['course' => $data['course'] ?? null, 'slot' => $data['slot'] ?? null], fn ($value) => $value !== null);
        $url = route('module-media.show', [$module, $revision, $asset] + $params);
        $headers = ['Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff', 'X-Frame-Options' => 'SAMEORIGIN'];
        if ($request->boolean('download') || ($request->boolean('inline') && $attachment['extension'] === 'pdf')) {
            $mime = match ($attachment['extension']) {
                'pdf' => 'application/pdf', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            };
            $response = $disk->response($attachment['path'], $attachment['name'], $headers + ['Content-Type' => $mime], $request->boolean('download') ? 'attachment' : 'inline');
            if (! $request->boolean('download')) {
                $response->headers->set('Content-Security-Policy', "sandbox; default-src 'none'");
            }

            return $response;
        }

        return response()->view('content.media-preview', compact('attachment', 'url'))->withHeaders($headers);
    }
}
