<?php

namespace App\Jobs\Challenges;

use App\Services\Challenges\SubmissionEvaluation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EvaluateSubmission implements ShouldQueue
{
    use Queueable;

    public int $tries = 1000;

    public int $timeout = 20;

    public function __construct(public readonly string $submissionId) {}

    public function handle(SubmissionEvaluation $evaluation): void
    {
        $delay = $evaluation->advance($this->submissionId);
        if ($delay !== null) {
            $this->release($delay);
        }
    }
}
