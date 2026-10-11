<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->constraint("IN ('2','3')", "'logic-switchboard','string-forge','memory-trail',");
    }

    public function down(): void
    {
        if (DB::table('game_preset_revisions')->whereRaw("instance->>'version' = '3' OR instance->>'template' IN ('logic-switchboard','string-forge','memory-trail')")->exists()) {
            throw new RuntimeException('Preserve expanded game snapshots; rollback requires removing their dependencies first.');
        }
        $this->constraint("= '2'");
    }

    private function constraint(string $quizVersions, string $extraTemplates = ''): void
    {
        DB::statement('ALTER TABLE game_preset_revisions DROP CONSTRAINT game_preset_revision_rules');
        DB::statement("ALTER TABLE game_preset_revisions ADD CONSTRAINT game_preset_revision_rules CHECK (number > 0 AND reward_mode = 'Deferred' AND scoring = 'ValidatedWin' AND participation = 'VerifiedActiveParticipants' AND jsonb_typeof(instance) = 'object' AND jsonb_exists(instance, 'template') AND jsonb_exists(instance, 'version') AND COALESCE(instance->>'template','') IN ($extraTemplates 'command-garden','choice-quiz','pixel-studio','number-machine','sort-lab','terminal-quest') AND COALESCE(jsonb_typeof(instance->'version'),'') = 'number' AND (instance->>'version' = '1' OR (instance->>'template' = 'choice-quiz' AND instance->>'version' $quizVersions)))");
    }
};
