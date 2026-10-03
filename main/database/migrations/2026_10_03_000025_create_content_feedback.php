<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['content_accesses', 'content_reactions'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('kind', 10);
                $table->foreignId('module_id')->nullable()->constrained('learning_modules')->restrictOnDelete();
                $table->foreignId('course_id')->nullable()->constrained('learning_courses')->restrictOnDelete();
                $table->foreignId('challenge_id')->nullable()->constrained('coding_challenges')->restrictOnDelete();
                if ($name === 'content_accesses') {
                    $table->timestampTz('first_opened_at');
                } else {
                    $table->string('reaction', 10)->nullable();
                    $table->integer('record_version');
                    $table->timestampsTz();
                }
                foreach (['module', 'course', 'challenge'] as $kind) {
                    $table->unique(['user_id', $kind.'_id']);
                    if ($name === 'content_reactions') {
                        $table->index([$kind.'_id', 'reaction']);
                    }
                }
            });
            DB::statement("ALTER TABLE {$name} ADD CONSTRAINT {$name}_target_check CHECK ((kind = 'module' AND module_id IS NOT NULL AND course_id IS NULL AND challenge_id IS NULL) OR (kind = 'course' AND course_id IS NOT NULL AND module_id IS NULL AND challenge_id IS NULL) OR (kind = 'challenge' AND challenge_id IS NOT NULL AND module_id IS NULL AND course_id IS NULL))");
        }
        DB::statement("ALTER TABLE content_reactions ADD CONSTRAINT content_reaction_check CHECK (record_version > 0 AND (reaction IS NULL OR reaction IN ('Like','Helpful','Favorite')))");
    }

    public function down(): void
    {
        if (DB::table('content_accesses')->exists() || DB::table('content_reactions')->exists()) {
            throw new RuntimeException('Preserve learner access and reaction records; do not revert populated feedback storage.');
        }
        Schema::dropIfExists('content_reactions');
        Schema::dropIfExists('content_accesses');
    }
};
