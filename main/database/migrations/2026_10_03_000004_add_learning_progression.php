<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_progress', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->timestampsTz();
        });
        DB::statement('ALTER TABLE learning_progress ADD CONSTRAINT streak_bounds CHECK (current_streak >= 0 AND longest_streak >= current_streak)');
        DB::statement('ALTER TABLE learning_progress ADD CONSTRAINT streak_date_consistent CHECK ((last_activity_date IS NULL AND current_streak = 0) OR (last_activity_date IS NOT NULL AND current_streak > 0))');
        Schema::create('learning_level_completions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('level', 32);
            $table->integer('template_version');
            $table->timestampTz('completed_at');
            $table->unique(['user_id', 'level']);
        });
        Schema::create('learning_activity_days', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('level', 32);
            $table->string('kind', 10);
            $table->integer('template_version');
            $table->date('business_date');
            $table->jsonb('validated_input');
            $table->timestampTz('completed_at');
            $table->unique(['user_id', 'level', 'kind', 'business_date']);
        });
        DB::statement("ALTER TABLE learning_activity_days ADD CONSTRAINT activity_kind_valid CHECK (kind IN ('game', 'quiz'))");
        DB::statement('ALTER TABLE learning_activity_days ADD CONSTRAINT activity_version_positive CHECK (template_version > 0)');
        DB::statement('ALTER TABLE learning_level_completions ADD CONSTRAINT completion_version_positive CHECK (template_version > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_activity_days');
        Schema::dropIfExists('learning_level_completions');
        Schema::dropIfExists('learning_progress');
    }
};
