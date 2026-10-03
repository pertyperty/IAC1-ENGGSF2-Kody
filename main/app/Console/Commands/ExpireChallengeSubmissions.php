<?php

namespace App\Console\Commands;

use App\Models\ChallengeSubmission;
use App\Services\Challenges\SubmissionEvaluation;
use Illuminate\Console\Command;

class ExpireChallengeSubmissions extends Command
{
    protected $signature = 'kody:submissions-expire';

    protected $description = 'Close overdue code evaluations without repeating remote execution';

    public function handle(SubmissionEvaluation $evaluation): int
    {
        ChallengeSubmission::whereNull('completed_at')->where('submitted_at', '<', now()->subSeconds(config('judge0.evaluation_seconds')))
            ->select('id')->chunkById(100, function ($submissions) use ($evaluation): void {
                foreach ($submissions as $submission) {
                    $evaluation->advance($submission->id);
                }
            });

        return self::SUCCESS;
    }
}
