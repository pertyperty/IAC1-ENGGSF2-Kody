<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\LearningModule;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CurriculumController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('create', LearningModule::class);

        return response()->view('content.curriculum', ['packs' => config('curriculum')])->header('Cache-Control', 'no-store, private');
    }
}
