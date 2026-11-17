<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\LearningModule;
use App\Services\Administration\ReviewInbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReviewInboxController extends Controller
{
    public function __invoke(Request $request, ReviewInbox $inbox): Response
    {
        Gate::authorize('viewAny', LearningModule::class);
        $data = $request->validate(['category' => ['sometimes', 'string', Rule::in(['all', ...array_keys(ReviewInbox::CATEGORIES)])]]);
        $category = $data['category'] ?? 'all';
        $items = $inbox->pending($request->user(), $category);
        $categories = ReviewInbox::CATEGORIES;

        return response()->view('administration.review-inbox', compact('items', 'category', 'categories'))->header('Cache-Control', 'no-store, private');
    }
}
