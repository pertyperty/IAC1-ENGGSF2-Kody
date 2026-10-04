<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->constraint("'command-garden','choice-quiz','pixel-studio','number-machine','sort-lab','terminal-quest'");
    }

    public function down(): void
    {
        if (DB::table('game_preset_revisions')->whereRaw("instance->>'template' NOT IN ('command-garden','choice-quiz')")->exists()) {
            throw new RuntimeException('Preserve new game preset versions; do not revert while these templates are referenced.');
        }
        $this->constraint("'command-garden','choice-quiz'");
    }

    private function constraint(string $templates): void
    {
        DB::statement('ALTER TABLE game_preset_revisions DROP CONSTRAINT game_preset_revision_rules');
        DB::statement("ALTER TABLE game_preset_revisions ADD CONSTRAINT game_preset_revision_rules CHECK (number > 0 AND reward_mode = 'Deferred' AND scoring = 'ValidatedWin' AND participation = 'VerifiedActiveParticipants' AND jsonb_typeof(instance) = 'object' AND jsonb_exists(instance, 'template') AND jsonb_exists(instance, 'version') AND COALESCE(instance->>'template','') IN ($templates) AND COALESCE(jsonb_typeof(instance->'version'),'') = 'number' AND instance->>'version' = '1')");
    }
};
