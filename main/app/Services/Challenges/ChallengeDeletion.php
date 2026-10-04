<?php

namespace App\Services\Challenges;

use App\Models\CodingChallenge;
use App\Services\Publishing\OwnedContentDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChallengeDeletion extends OwnedContentDeletion
{
    protected function modelClass(string $kind): string
    {
        abort_unless($kind === 'challenge', 404);

        return CodingChallenge::class;
    }

    protected function specificDependencies(string $kind, Model $content): array
    {
        return [
            'Submissions and evaluation history' => DB::table('challenge_participations')->where('challenge_id', $content->id)->exists(),
            'Learner submission audit history' => DB::table('audit_events')->where('event', 'challenge.attempted')->where('context->challenge_id', $content->id)->exists(),
            'Weekly event history' => DB::table('weekly_events')->where('challenge_id', $content->id)->exists(),
            'Weekly planning audit history' => DB::table('audit_events')->where('subject_type', 'weekly_event')->where('context->challenge_id', $content->id)->exists(),
        ];
    }

    protected function removeDefinition(string $kind, Model $content): void
    {
        $revisions = DB::table('coding_challenge_revisions')->where('challenge_id', $content->id)->select('id');
        DB::table('challenge_test_cases')->whereIn('revision_id', $revisions)->delete();
        DB::table('coding_challenge_revisions')->where('challenge_id', $content->id)->delete();
    }
}
