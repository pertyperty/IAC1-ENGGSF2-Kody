<?php

namespace App\Services\Challenges;

use App\Models\ChallengeCaseEvaluation;
use App\Models\ChallengeSubmission;

class AttemptResults
{
    public function summarize(ChallengeSubmission $submission): array
    {
        $evaluated = 0;
        $issues = [];
        $cases = ChallengeCaseEvaluation::where('submission_id', $submission->id)
            ->whereIn('status', ['Passed', 'Failed'])
            ->selectRaw('status, provider_status, count(*) as case_count')
            ->groupBy('status', 'provider_status')->orderBy('provider_status')->toBase()->get();

        foreach ($cases as $case) {
            $evaluated += (int) $case->case_count;
            if ($submission->completed_at === null || $case->status !== 'Failed') {
                continue;
            }
            [$label, $hint] = match ((int) $case->provider_status) {
                4 => ['Wrong output', 'Try the public examples and compare spacing, line breaks, and edge cases.'],
                5 => ['Time limit exceeded', 'Check whether a loop takes too many steps or never stops.'],
                6 => ['Compilation error', $submission->language === 'java'
                    ? 'Check syntax and confirm your Main class has a valid main method.'
                    : 'Check syntax, names, and imports before submitting again.'],
                14 => ['Invalid program', 'Check that the solution is a complete program in the selected language.'],
                default => ['Runtime error', 'Check input handling, indexes, and operations that can fail at runtime.'],
            };
            $issues[$label] ??= ['label' => $label, 'hint' => $hint, 'count' => 0];
            $issues[$label]['count'] += (int) $case->case_count;
        }

        return ['evaluated_cases' => $evaluated, 'issues' => array_values($issues)];
    }
}
