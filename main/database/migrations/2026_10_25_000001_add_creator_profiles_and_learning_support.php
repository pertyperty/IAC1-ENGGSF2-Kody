<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_profiles', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->restrictOnDelete();
            $table->string('headline', 100)->nullable();
            $table->text('bio')->nullable();
            $table->text('accomplishments')->nullable();
            $table->unsignedInteger('record_version')->default(1);
            $table->timestampsTz();
        });
        DB::statement('ALTER TABLE creator_profiles ADD CONSTRAINT creator_profile_lengths CHECK (record_version > 0 AND char_length(bio) <= 1000 AND char_length(accomplishments) <= 1000)');
        Schema::create('creator_verifications', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->restrictOnDelete();
            $table->string('role', 20);
            $table->boolean('active')->default(false);
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('reviewed_at');
            $table->foreignId('instructor_application_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('contributor_application_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('record_version')->default(1);
        });
        DB::statement("ALTER TABLE creator_verifications ADD CONSTRAINT creator_verification_proof CHECK (record_version > 0 AND user_id <> reviewed_by AND ((role = 'Instructor' AND instructor_application_id IS NOT NULL AND contributor_application_id IS NULL) OR (role = 'Contributor' AND contributor_application_id IS NOT NULL AND instructor_application_id IS NULL)))");
        Schema::create('tower_review_items', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('level_id')->constrained('tower_levels')->restrictOnDelete();
            $table->foreignId('revision_id')->constrained('tower_revisions')->restrictOnDelete();
            $table->timestampTz('created_at');
            $table->primary(['user_id', 'level_id']);
            $table->foreign(['revision_id', 'level_id'])->references(['id', 'level_id'])->on('tower_revisions')->restrictOnDelete();
        });
        Schema::create('operations_heartbeats', function (Blueprint $table): void {
            $table->string('component', 30)->primary();
            $table->timestampTz('last_success_at')->nullable();
            $table->timestampTz('last_dispatched_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_heartbeats');
        Schema::dropIfExists('tower_review_items');
        Schema::dropIfExists('creator_verifications');
        Schema::dropIfExists('creator_profiles');
    }
};
