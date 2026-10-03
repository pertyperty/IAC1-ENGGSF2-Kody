<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE UNIQUE INDEX notifications_course_review_unique ON notifications (notifiable_id, (data->>'revision_id')) WHERE type = 'course.reviewed'");
        Schema::create('learning_courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 150);
            $table->string('category', 50);
            $table->string('status', 12)->default('Draft');
            $table->integer('record_version')->default(1);
            $table->unsignedBigInteger('published_revision_id')->nullable();
            $table->timestampsTz();
            $table->index(['created_by', 'id']);
        });
        DB::statement('CREATE UNIQUE INDEX learning_courses_title_category_unique ON learning_courses (lower(btrim(title)), lower(btrim(category)))');
        Schema::create('course_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->restrictOnDelete();
            $table->integer('number');
            $table->string('title', 150);
            $table->text('description');
            $table->string('category', 50);
            $table->string('difficulty', 20);
            $table->integer('estimated_duration');
            $table->string('review_status', 12)->default('Draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_notes', 255)->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['course_id', 'number']);
            $table->unique(['id', 'course_id']);
            $table->index(['review_status', 'id']);
        });
        DB::statement("ALTER TABLE learning_courses ADD CONSTRAINT learning_courses_status_check CHECK (status IN ('Draft','Published','Archived','Deleted')), ADD CONSTRAINT learning_courses_version_check CHECK (record_version > 0), ADD CONSTRAINT learning_courses_publication_check CHECK ((status = 'Draft' AND published_revision_id IS NULL) OR (status <> 'Draft' AND published_revision_id IS NOT NULL))");
        DB::statement('ALTER TABLE learning_courses ADD CONSTRAINT learning_courses_published_revision_fk FOREIGN KEY (published_revision_id, id) REFERENCES course_revisions (id, course_id)');
        DB::statement("ALTER TABLE course_revisions ADD CONSTRAINT course_revisions_number_check CHECK (number > 0), ADD CONSTRAINT course_revisions_duration_check CHECK (estimated_duration > 0), ADD CONSTRAINT course_revisions_difficulty_check CHECK (difficulty IN ('Beginner','Intermediate','Advanced')), ADD CONSTRAINT course_revisions_review_check CHECK (review_status IN ('Draft','Pending','Approved','Rejected'))");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX notifications_course_review_unique');
        DB::statement('ALTER TABLE learning_courses DROP CONSTRAINT learning_courses_published_revision_fk');
        Schema::dropIfExists('course_revisions');
        Schema::dropIfExists('learning_courses');
    }
};
