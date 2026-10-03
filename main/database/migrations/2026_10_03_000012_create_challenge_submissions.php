<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judge0_profiles', function (Blueprint $table): void {
            $table->id();
            $table->char('fingerprint', 64)->unique();
            $table->jsonb('languages');
            $table->jsonb('limits');
            $table->timestampTz('verified_at');
        });
        Schema::create('challenge_participations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('challenge_id')->constrained('coding_challenges')->restrictOnDelete();
            $table->integer('attempts')->default(0);
            $table->uuid('active_submission_id')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'challenge_id']);
            $table->unique(['id', 'challenge_id']);
        });
        Schema::create('challenge_submissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('participation_id');
            $table->unsignedBigInteger('challenge_id');
            $table->unsignedBigInteger('revision_id');
            $table->foreignId('provider_profile_id')->constrained('judge0_profiles')->restrictOnDelete();
            $table->integer('attempt');
            $table->uuid('confirmation_id');
            $table->char('request_digest', 64);
            $table->text('source_code');
            $table->string('language', 10);
            $table->string('status', 12)->default('Queued');
            $table->integer('passed_cases')->default(0);
            $table->integer('total_cases');
            $table->string('feedback', 500)->nullable();
            $table->timestampTz('submitted_at');
            $table->timestampTz('completed_at')->nullable();
            $table->uuid('lease_id')->nullable();
            $table->timestampTz('lease_expires_at')->nullable();
            $table->unique(['participation_id', 'attempt']);
            $table->unique(['participation_id', 'confirmation_id']);
            $table->unique(['id', 'participation_id']);
            $table->unique(['id', 'revision_id']);
            $table->foreign(['participation_id', 'challenge_id'])->references(['id', 'challenge_id'])->on('challenge_participations')->restrictOnDelete();
            $table->foreign(['revision_id', 'challenge_id'])->references(['id', 'challenge_id'])->on('coding_challenge_revisions')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE challenge_test_cases ADD CONSTRAINT challenge_test_cases_id_revision_unique UNIQUE (id, revision_id)');
        Schema::create('challenge_case_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('submission_id');
            $table->unsignedBigInteger('revision_id');
            $table->unsignedBigInteger('test_case_id');
            $table->string('status', 12)->default('Queued');
            $table->text('provider_token')->nullable();
            $table->integer('provider_status')->nullable();
            $table->unique(['submission_id', 'test_case_id']);
            $table->foreign(['submission_id', 'revision_id'])->references(['id', 'revision_id'])->on('challenge_submissions')->restrictOnDelete();
            $table->foreign(['test_case_id', 'revision_id'])->references(['id', 'revision_id'])->on('challenge_test_cases')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE challenge_participations ADD CONSTRAINT challenge_participations_attempts_check CHECK (attempts BETWEEN 0 AND 3), ADD CONSTRAINT challenge_participations_active_fk FOREIGN KEY (active_submission_id, id) REFERENCES challenge_submissions (id, participation_id)');
        DB::statement("ALTER TABLE challenge_submissions ADD CONSTRAINT challenge_submissions_values_check CHECK (attempt BETWEEN 1 AND 3 AND language IN ('python','java','cpp') AND status IN ('Queued','Evaluating','Passed','Failed','Unavailable') AND total_cases BETWEEN 1 AND 20 AND passed_cases BETWEEN 0 AND total_cases AND ((status IN ('Queued','Evaluating') AND completed_at IS NULL) OR (status IN ('Passed','Failed','Unavailable') AND completed_at IS NOT NULL)))");
        DB::statement("CREATE UNIQUE INDEX challenge_submissions_one_active ON challenge_submissions (participation_id) WHERE status IN ('Queued','Evaluating')");
        DB::statement('CREATE INDEX challenge_submissions_overdue ON challenge_submissions (submitted_at) WHERE completed_at IS NULL');
        DB::statement("ALTER TABLE challenge_submissions ADD CONSTRAINT challenge_submissions_outcome_check CHECK ((status <> 'Passed' OR passed_cases = total_cases) AND (status <> 'Failed' OR passed_cases < total_cases)), ADD CONSTRAINT challenge_submissions_lease_check CHECK ((lease_id IS NULL) = (lease_expires_at IS NULL))");
        DB::statement("ALTER TABLE challenge_case_evaluations ADD CONSTRAINT challenge_case_evaluations_values_check CHECK (status IN ('Queued','Creating','Submitted','Passed','Failed','Unavailable') AND (provider_status IS NULL OR provider_status BETWEEN 1 AND 14))");
        DB::statement("ALTER TABLE challenge_case_evaluations ADD CONSTRAINT challenge_case_evaluations_token_check CHECK (status NOT IN ('Submitted','Passed','Failed') OR provider_token IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_case_evaluations');
        DB::statement('ALTER TABLE challenge_participations DROP CONSTRAINT challenge_participations_active_fk');
        Schema::dropIfExists('challenge_submissions');
        Schema::dropIfExists('challenge_participations');
        Schema::dropIfExists('judge0_profiles');
        DB::statement('ALTER TABLE challenge_test_cases DROP CONSTRAINT challenge_test_cases_id_revision_unique');
    }
};
