<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 10)->default('Active');
            $table->integer('record_version')->default(1);
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'id']);
        });
        DB::statement('CREATE UNIQUE INDEX game_presets_name_unique ON game_presets (lower(btrim(name)))');
        DB::statement("ALTER TABLE game_presets ADD CONSTRAINT game_presets_state_check CHECK (status IN ('Active','Inactive') AND record_version > 0 AND char_length(btrim(name)) > 0)");
        Schema::create('game_preset_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('preset_id')->constrained('game_presets')->restrictOnDelete();
            $table->integer('number');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->jsonb('instance');
            $table->string('reward_mode', 10)->default('Deferred');
            $table->string('scoring', 20)->default('ValidatedWin');
            $table->string('participation', 30)->default('VerifiedActiveParticipants');
            $table->timestampsTz();
            $table->unique(['preset_id', 'number']);
            $table->unique(['id', 'preset_id']);
        });
        DB::statement("ALTER TABLE game_preset_revisions ADD CONSTRAINT game_preset_revision_rules CHECK (number > 0 AND reward_mode = 'Deferred' AND scoring = 'ValidatedWin' AND participation = 'VerifiedActiveParticipants' AND jsonb_typeof(instance) = 'object' AND jsonb_exists(instance, 'template') AND jsonb_exists(instance, 'version') AND COALESCE(instance->>'template','') IN ('command-garden','choice-quiz') AND COALESCE(jsonb_typeof(instance->'version'),'') = 'number' AND instance->>'version' = '1')");
        DB::statement('ALTER TABLE game_presets ADD CONSTRAINT game_presets_current_revision_fk FOREIGN KEY (current_revision_id,id) REFERENCES game_preset_revisions (id,preset_id)');
        Schema::table('module_revisions', fn (Blueprint $table) => $table->foreignId('game_preset_revision_id')->nullable()->constrained('game_preset_revisions')->restrictOnDelete());
        DB::statement('ALTER TABLE module_revisions ADD CONSTRAINT module_preset_assessment_check CHECK (game_preset_revision_id IS NULL OR assessment IS NOT NULL)');
        DB::unprepared("CREATE OR REPLACE FUNCTION preserve_game_preset_revision() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Game preset revisions are immutable'; END; $$");
        DB::statement('CREATE TRIGGER game_preset_revision_immutable BEFORE UPDATE OR DELETE ON game_preset_revisions FOR EACH ROW EXECUTE FUNCTION preserve_game_preset_revision()');
    }

    public function down(): void
    {
        if (DB::table('game_presets')->exists()) {
            throw new RuntimeException('Preserve preset versions and module references; do not revert this migration after presets exist.');
        }
        DB::statement('ALTER TABLE module_revisions DROP CONSTRAINT module_preset_assessment_check');
        Schema::table('module_revisions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('game_preset_revision_id');
        });
        DB::statement('ALTER TABLE game_presets DROP CONSTRAINT game_presets_current_revision_fk');
        Schema::dropIfExists('game_preset_revisions');
        DB::statement('DROP FUNCTION preserve_game_preset_revision()');
        Schema::dropIfExists('game_presets');
    }
};
