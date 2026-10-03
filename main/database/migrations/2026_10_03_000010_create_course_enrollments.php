<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->restrictOnDelete();
            $table->unsignedBigInteger('course_revision_id');
            $table->timestampTz('enrolled_at');
            $table->unique(['user_id', 'course_id']);
            $table->unique(['id', 'course_revision_id']);
            $table->foreign(['course_revision_id', 'course_id'])->references(['id', 'course_id'])->on('course_revisions')->restrictOnDelete();
            $table->index(['user_id', 'enrolled_at']);
            $table->index(['course_revision_id', 'course_id']);
            $table->index('course_id');
        });
        Schema::table('course_revision_modules', function (Blueprint $table): void {
            $table->unique(['id', 'course_revision_id']);
        });
        Schema::create('course_module_progress', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('course_revision_id');
            $table->timestampTz('first_accessed_at');
            $table->timestampTz('last_accessed_at');
            $table->timestampTz('completed_at')->nullable();
            $table->jsonb('validated_input')->nullable();
            $table->unique(['enrollment_id', 'assignment_id']);
            $table->index(['assignment_id', 'course_revision_id']);
            $table->foreign(['enrollment_id', 'course_revision_id'])->references(['id', 'course_revision_id'])->on('course_enrollments')->restrictOnDelete();
            $table->foreign(['assignment_id', 'course_revision_id'])->references(['id', 'course_revision_id'])->on('course_revision_modules')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE course_module_progress ADD CONSTRAINT course_module_progress_completion_check CHECK ((completed_at IS NULL) = (validated_input IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('course_module_progress');
        Schema::dropIfExists('course_enrollments');
        Schema::table('course_revision_modules', function (Blueprint $table): void {
            $table->dropUnique(['id', 'course_revision_id']);
        });
    }
};
