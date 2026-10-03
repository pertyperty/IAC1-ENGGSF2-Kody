<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE UNIQUE INDEX notifications_challenge_review_unique ON notifications (notifiable_id, (data->>'revision_id')) WHERE type = 'challenge.reviewed'");
        Schema::create('coding_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 12)->default('Draft');
            $table->integer('record_version')->default(1);
            $table->unsignedBigInteger('published_revision_id')->nullable();
            $table->timestampsTz();
            $table->index(['created_by', 'id']);
        });
        Schema::create('coding_challenge_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('challenge_id')->constrained('coding_challenges')->restrictOnDelete();
            $table->integer('number');
            $table->string('title', 150);
            $table->text('description');
            $table->string('language', 10);
            $table->string('difficulty', 10);
            $table->text('rules');
            $table->text('input_format');
            $table->text('output_format');
            $table->integer('cpu_time_ms');
            $table->integer('memory_kib');
            $table->string('review_status', 12)->default('Draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_notes', 255)->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['challenge_id', 'number']);
            $table->unique(['id', 'challenge_id']);
            $table->index(['review_status', 'id']);
        });
        Schema::create('challenge_test_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('revision_id')->constrained('coding_challenge_revisions')->restrictOnDelete();
            $table->integer('position');
            $table->text('input');
            $table->text('expected_output');
            $table->boolean('hidden');
            $table->unique(['revision_id', 'position']);
        });
        DB::statement("ALTER TABLE coding_challenges ADD CONSTRAINT coding_challenges_status_check CHECK (status IN ('Draft','Published','Archived','Deleted')), ADD CONSTRAINT coding_challenges_version_check CHECK (record_version > 0), ADD CONSTRAINT coding_challenges_publication_check CHECK ((status = 'Draft' AND published_revision_id IS NULL) OR (status <> 'Draft' AND published_revision_id IS NOT NULL)), ADD CONSTRAINT coding_challenges_revision_fk FOREIGN KEY (published_revision_id, id) REFERENCES coding_challenge_revisions (id, challenge_id)");
        DB::statement("ALTER TABLE coding_challenge_revisions ADD CONSTRAINT coding_challenge_revisions_values_check CHECK (number > 0 AND language IN ('python','java','cpp') AND difficulty IN ('Easy','Medium','Hard') AND review_status IN ('Draft','Pending','Approved','Rejected') AND cpu_time_ms BETWEEN 100 AND 5000 AND memory_kib BETWEEN 16384 AND 262144)");
        DB::statement('ALTER TABLE challenge_test_cases ADD CONSTRAINT challenge_test_cases_bounds_check CHECK (position BETWEEN 1 AND 20 AND octet_length(input) <= 65536 AND octet_length(expected_output) <= 65536)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX notifications_challenge_review_unique');
        DB::statement('ALTER TABLE coding_challenges DROP CONSTRAINT coding_challenges_revision_fk');
        Schema::dropIfExists('challenge_test_cases');
        Schema::dropIfExists('coding_challenge_revisions');
        Schema::dropIfExists('coding_challenges');
    }
};
