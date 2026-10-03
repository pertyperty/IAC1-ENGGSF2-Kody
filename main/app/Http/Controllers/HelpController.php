<?php

namespace App\Http\Controllers;

use App\Models\FaqEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class HelpController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80'], 'category' => ['nullable', Rule::in(array_keys(config('help.categories')))]]);
        $query = FaqEntry::where('status', 'Active');
        if (trim($filters['q'] ?? '') !== '') {
            $literal = '%'.addcslashes(trim($filters['q']), '%_\\').'%';
            $query->where(fn ($search) => $search->where('question', 'ilike', $literal)->orWhere('answer', 'ilike', $literal));
        }
        if (($filters['category'] ?? '') !== '') {
            $query->where('category', $filters['category']);
        }
        $entries = $query->orderBy('category')->orderBy('id')->paginate(12)->appends($filters);

        return response()->view('help.index', compact('entries', 'filters'))->header('Cache-Control', 'no-store, private');
    }

    public function show(FaqEntry $entry): Response
    {
        abort_unless($entry->status === 'Active', 404);

        return response()->view('help.show', compact('entry'))->header('Cache-Control', 'no-store, private');
    }
}
