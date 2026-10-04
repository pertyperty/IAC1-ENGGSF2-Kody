<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->constraint(true);
    }

    public function down(): void
    {
        if (DB::table('game_preset_revisions')->whereRaw("instance->>'version' = '2'")->exists()) {
            throw new RuntimeException('Preserve version-2 quiz snapshots; this migration cannot be reverted while they exist.');
        }
        $this->constraint(false);
    }

    private function constraint(bool $expanded): void
    {
        $version = $expanded ? "(instance->>'version' = '1' OR (instance->>'template' = 'choice-quiz' AND instance->>'version' = '2'))" : "instance->>'version' = '1'";
        DB::statement('ALTER TABLE game_preset_revisions DROP CONSTRAINT game_preset_revision_rules');
        DB::statement("ALTER TABLE game_preset_revisions ADD CONSTRAINT game_preset_revision_rules CHECK (number > 0 AND reward_mode = 'Deferred' AND scoring = 'ValidatedWin' AND participation = 'VerifiedActiveParticipants' AND jsonb_typeof(instance) = 'object' AND jsonb_exists(instance, 'template') AND jsonb_exists(instance, 'version') AND COALESCE(instance->>'template','') IN ('command-garden','choice-quiz','pixel-studio','number-machine','sort-lab','terminal-quest') AND COALESCE(jsonb_typeof(instance->'version'),'') = 'number' AND $version)");
    }
};
