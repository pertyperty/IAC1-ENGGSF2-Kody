<?php

namespace App\Services\Challenges;

use App\Models\ChallengeSubmission;
use App\Models\CodingChallengeRevision;
use Illuminate\Support\Collection;

class RetrySource
{
    /** @param Collection<int, ChallengeSubmission> $attempts Already scoped to the current learner and participation. */
    public function read(Collection $attempts, CodingChallengeRevision $revision, mixed $selected): ?string
    {
        if ($selected !== null && ! is_string($selected)) {
            return null;
        }
        $attempt = $attempts->first(fn (ChallengeSubmission $attempt) => ($selected === null || $attempt->id === $selected)
            && (string) $attempt->revision_id === (string) $revision->id
            && in_array($attempt->status, ['Failed', 'Unavailable'], true));

        return $attempt === null ? null : ChallengeSubmission::whereKey($attempt->id)->firstOrFail(['id', 'source_code'])->source_code;
    }
}
