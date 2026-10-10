<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tower_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('position')->unique();
            $table->unsignedInteger('record_version')->default(1);
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->timestampsTz();
        });
        Schema::create('tower_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained('tower_levels')->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->string('title', 100);
            $table->string('concept', 100);
            $table->text('description');
            $table->jsonb('stages');
            $table->text('source_notes');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at');
            $table->unique(['level_id', 'number']);
            $table->unique(['id', 'level_id']);
        });
        DB::statement('ALTER TABLE tower_levels ADD CONSTRAINT tower_revision_owned FOREIGN KEY (current_revision_id, id) REFERENCES tower_revisions (id, level_id)');
        DB::statement('ALTER TABLE tower_levels ADD CONSTRAINT tower_position_valid CHECK (position BETWEEN 1 AND 1000 AND record_version > 0)');
        DB::statement("ALTER TABLE tower_revisions ADD CONSTRAINT tower_stages_valid CHECK (number > 0 AND jsonb_typeof(stages) = 'array' AND jsonb_array_length(stages) BETWEEN 1 AND 4)");
        Schema::create('tower_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('level_id')->constrained('tower_levels')->restrictOnDelete();
            $table->unsignedBigInteger('revision_id');
            $table->timestampTz('completed_at');
            $table->unique(['user_id', 'level_id']);
            $table->foreign(['revision_id', 'level_id'])->references(['id', 'level_id'])->on('tower_revisions')->restrictOnDelete();
        });
        Schema::create('tower_stage_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('revision_id')->constrained('tower_revisions')->restrictOnDelete();
            $table->unsignedInteger('stage');
            $table->timestampTz('completed_at');
            $table->unique(['user_id', 'revision_id', 'stage']);
        });
        DB::statement('ALTER TABLE tower_stage_completions ADD CONSTRAINT tower_stage_valid CHECK (stage BETWEEN 0 AND 3)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tower_stage_completions');
        Schema::dropIfExists('tower_clearances');
        DB::statement('ALTER TABLE tower_levels DROP CONSTRAINT tower_revision_owned');
        Schema::dropIfExists('tower_revisions');
        Schema::dropIfExists('tower_levels');
    }
};
