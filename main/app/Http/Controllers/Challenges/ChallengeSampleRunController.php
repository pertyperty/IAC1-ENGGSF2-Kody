<?php

namespace App\Http\Controllers\Challenges;

use App\Models\CodingChallenge;
use App\Services\Challenges\ChallengeSampleRuns;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeSampleRunController
{
    public function __invoke(Request $request, CodingChallenge $challenge, ChallengeSampleRuns $runs): JsonResponse
    {
        abort_unless($runs->available(), 503, 'Sample runs are not available.');
        abort_unless($request->expectsJson(), 406, 'Request a JSON response for a sample run.');
        $data = $request->validate(['revision_id' => ['required', 'integer', 'min:1'],
            'language' => ['required', 'string', 'max:20'], 'source_code' => ['required', 'string']]);
        $results = $runs->run($request->user(), $request->session()->getId(), $challenge, (int) $data['revision_id'], $data['language'], $data['source_code']);

        return response()->json(['samples' => $results])->header('Cache-Control', 'no-store, private');
    }
}
