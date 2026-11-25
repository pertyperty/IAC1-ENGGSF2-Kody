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
                7 => ['Runtime error: invalid memory access', 'Check array bounds, references, and pointer use.'],
                8 => ['Runtime error: file size limit', 'Check for unexpected writes or runaway output.'],
                9 => ['Runtime error: arithmetic fault', 'Check for division by zero or invalid arithmetic.'],
                10 => ['Runtime error: program aborted', 'Check failed assertions or uncaught exceptions.'],
                11 => ['Runtime error: nonzero exit', 'Check for uncaught exceptions and invalid input handling.'],
                14 => ['Invalid program', 'Check that the solution is a complete program in the selected language.'],
                default => ['Runtime error', 'Check input handling, indexes, and operations that can fail at runtime.'],
            };
            $issues[$label] ??= ['label' => $label, 'hint' => $hint, 'count' => 0];
            $issues[$label]['count'] += (int) $case->case_count;
        }

        return ['evaluated_cases' => $evaluated, 'issues' => array_values($issues)]
            + ($submission->completed_at === null ? [] : $this->details($submission));
    }

    private function details(ChallengeSubmission $submission): array
    {
        $cases = ChallengeCaseEvaluation::query()->from('challenge_case_evaluations as evaluation')
            ->join('challenge_test_cases as test', 'test.id', '=', 'evaluation.test_case_id')
            ->where('evaluation.submission_id', $submission->id)->orderBy('test.position')
            ->select('evaluation.status', 'evaluation.provider_status', 'evaluation.result_details', 'test.hidden')
            ->selectRaw('CASE WHEN NOT test.hidden THEN test.input END AS input, CASE WHEN NOT test.hidden THEN test.expected_output END AS expected_output')
            ->withCasts(['hidden' => 'boolean'])->get();
        $examples = [];
        $locations = [];
        $times = [];
        $memory = [];
        $sampleNumber = 0;
        foreach ($cases as $case) {
            $details = $case->result_details ?? [];
            if ($case->status === 'Passed') {
                if (isset($details['cpu_ms'])) {
                    $times[] = $details['cpu_ms'];
                }
                if (isset($details['memory_kib'])) {
                    $memory[] = $details['memory_kib'];
                }
            }
            if ($case->hidden) {
                continue;
            }
            $sampleNumber++;
            if ($case->provider_status === 4) {
                $examples[] = ['number' => $sampleNumber, 'input' => $case->input, 'expected' => $case->expected_output,
                    'output' => $details['output'] ?? null, 'truncated' => $details['output_truncated'] ?? false];
            }
            foreach ($details['lines'] ?? [] as $line) {
                $locations[$line] = $case->provider_status === 6 ? 'Compilation error' : 'Runtime error';
            }
        }
        ksort($locations);

        return [
            'examples' => $examples,
            'locations' => array_slice($locations, 0, 5, true),
            'statistics' => [
                'slowest_ms' => $submission->status === 'Passed' && count($times) === $cases->count() && $times !== [] ? max($times) : null,
                'peak_kib' => $submission->status === 'Passed' && count($memory) === $cases->count() && $memory !== [] ? max($memory) : null,
            ],
        ];
    }
}
