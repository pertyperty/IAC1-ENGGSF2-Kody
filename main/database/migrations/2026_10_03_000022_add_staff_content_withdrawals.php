<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['module' => 'learning_modules', 'course' => 'learning_courses', 'challenge' => 'coding_challenges'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->timestampTz('staff_withdrawn_at')->nullable());
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_withdrawal_state CHECK (staff_withdrawn_at IS NULL OR status IN ('Published','Archived'))");
        }
        Schema::create('content_moderation_actions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('learning_modules')->restrictOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('learning_courses')->restrictOnDelete();
            $table->foreignId('challenge_id')->nullable()->constrained('coding_challenges')->restrictOnDelete();
            $table->string('kind', 10);
            $table->string('action', 12);
            $table->string('lifecycle', 10);
            $table->integer('record_version');
            $table->timestampsTz();
            foreach (['module', 'course', 'challenge'] as $kind) {
                $table->unique([$kind.'_id', 'record_version']);
            }
        });
        DB::statement("ALTER TABLE content_moderation_actions ADD CONSTRAINT content_moderation_action_check CHECK (action IN ('Withdrawn','Restored') AND lifecycle IN ('Published','Archived') AND record_version > 1 AND ((kind = 'module' AND module_id IS NOT NULL AND course_id IS NULL AND challenge_id IS NULL) OR (kind = 'course' AND course_id IS NOT NULL AND module_id IS NULL AND challenge_id IS NULL) OR (kind = 'challenge' AND challenge_id IS NOT NULL AND module_id IS NULL AND course_id IS NULL)))");
    }

    public function down(): void
    {
        if (DB::table('content_moderation_actions')->exists()) {
            throw new RuntimeException('Preserve staff content moderation history; roll back application code without reverting this migration.');
        }
        foreach ($this->tables as $table) {
            if (DB::table($table)->whereNotNull('staff_withdrawn_at')->exists()) {
                throw new RuntimeException('Preserve staff withdrawal blocks during application rollback.');
            }
        }
        Schema::dropIfExists('content_moderation_actions');
        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$table}_withdrawal_state");
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('staff_withdrawn_at'));
        }
    }
};
