<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 12)->default('Draft');
            $table->unsignedInteger('record_version')->default(1);
            $table->unsignedBigInteger('published_revision_id')->nullable();
            $table->timestampsTz();
            $table->index(['created_by', 'id']);
        });
        Schema::create('module_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained('learning_modules')->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->string('title', 150);
            $table->text('description');
            $table->text('content');
            $table->string('type', 12);
            $table->text('video_url')->nullable();
            $table->jsonb('assessment')->nullable();
            $table->string('review_status', 12)->default('Draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_notes', 255)->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['module_id', 'number']);
            $table->unique(['id', 'module_id']);
            $table->index(['review_status', 'id']);
        });
        DB::statement("ALTER TABLE learning_modules ADD CONSTRAINT learning_modules_status_check CHECK (status IN ('Draft','Published','Archived','Deleted')), ADD CONSTRAINT learning_modules_version_check CHECK (record_version > 0), ADD CONSTRAINT learning_modules_publication_check CHECK ((status = 'Draft' AND published_revision_id IS NULL) OR (status <> 'Draft' AND published_revision_id IS NOT NULL))");
        // A module cannot point at another author's module revision.
        DB::statement('ALTER TABLE learning_modules ADD CONSTRAINT learning_modules_published_revision_fk FOREIGN KEY (published_revision_id, id) REFERENCES module_revisions (id, module_id)');
        DB::statement("ALTER TABLE module_revisions ADD CONSTRAINT module_revisions_number_check CHECK (number > 0), ADD CONSTRAINT module_revisions_type_check CHECK (type IN ('Article','Interactive','Video')), ADD CONSTRAINT module_revisions_review_check CHECK (review_status IN ('Draft','Pending','Approved','Rejected')), ADD CONSTRAINT module_revisions_interactive_check CHECK (type <> 'Interactive' OR assessment IS NOT NULL), ADD CONSTRAINT module_revisions_video_check CHECK (type <> 'Video' OR video_url IS NOT NULL)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE learning_modules DROP CONSTRAINT learning_modules_published_revision_fk');
        Schema::dropIfExists('module_revisions');
        Schema::dropIfExists('learning_modules');
    }
};
