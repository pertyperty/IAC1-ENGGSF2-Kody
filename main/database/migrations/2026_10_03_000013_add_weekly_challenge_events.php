<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_calendar_locks', function (Blueprint $table): void {
            $table->integer('id')->primary();
        });
        DB::table('weekly_calendar_locks')->insert(['id' => 1]);
        DB::statement('ALTER TABLE weekly_calendar_locks ADD CONSTRAINT weekly_calendar_singleton CHECK (id = 1)');
        Schema::create('weekly_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('challenge_id');
            $table->unsignedBigInteger('revision_id');
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('starts_at')->unique();
            $table->timestampTz('ends_at');
            $table->text('rules');
            $table->string('status', 12)->default('Scheduled');
            $table->string('reward_mode', 12)->default('Deferred');
            $table->integer('record_version')->default(1);
            $table->timestampsTz();
            $table->unique(['id', 'challenge_id']);
            $table->unique(['id', 'challenge_id', 'revision_id']);
            $table->foreign(['revision_id', 'challenge_id'])->references(['id', 'challenge_id'])->on('coding_challenge_revisions')->restrictOnDelete();
            $table->index(['status', 'ends_at']);
        });
        DB::statement("ALTER TABLE weekly_events ADD CONSTRAINT weekly_events_values_check CHECK (status IN ('Scheduled','Active','Ended','Unavailable') AND record_version > 0 AND reward_mode = 'Deferred'), ADD CONSTRAINT weekly_events_window_check CHECK (ends_at = starts_at + INTERVAL '7 days' AND EXTRACT(DOW FROM starts_at AT TIME ZONE 'Asia/Manila') = 0 AND (starts_at AT TIME ZONE 'Asia/Manila')::time = TIME '00:00:00')");
        DB::statement("CREATE UNIQUE INDEX weekly_events_one_active ON weekly_events ((1)) WHERE status = 'Active'");
        Schema::table('challenge_participations', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'challenge_id']);
            $table->unsignedBigInteger('weekly_event_id')->nullable();
            $table->unsignedBigInteger('context_key')->storedAs('COALESCE(weekly_event_id, 0)');
            $table->unique(['user_id', 'challenge_id', 'context_key'], 'participations_user_context_unique');
            $table->unique(['id', 'challenge_id', 'context_key'], 'participations_context_reference_unique');
            $table->foreign(['weekly_event_id', 'challenge_id'], 'participations_weekly_event_fk')->references(['id', 'challenge_id'])->on('weekly_events')->restrictOnDelete();
        });
        Schema::table('challenge_submissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('weekly_event_id')->nullable();
            $table->unsignedBigInteger('context_key')->storedAs('COALESCE(weekly_event_id, 0)');
            $table->foreign(['participation_id', 'challenge_id', 'context_key'], 'submissions_participation_context_fk')->references(['id', 'challenge_id', 'context_key'])->on('challenge_participations')->restrictOnDelete();
            $table->foreign(['weekly_event_id', 'challenge_id', 'revision_id'], 'submissions_weekly_revision_fk')->references(['id', 'challenge_id', 'revision_id'])->on('weekly_events')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Down is intentionally blocked when weekly history exists: never mix budgets.
        if (DB::table('challenge_participations')->whereNotNull('weekly_event_id')->exists()) {
            throw new RuntimeException('Weekly participation history must be preserved. Roll back application releases without reverting this migration.');
        }
        Schema::table('challenge_submissions', function (Blueprint $table): void {
            $table->dropForeign('submissions_participation_context_fk');
            $table->dropForeign('submissions_weekly_revision_fk');
            $table->dropColumn(['context_key', 'weekly_event_id']);
        });
        Schema::table('challenge_participations', function (Blueprint $table): void {
            $table->dropForeign('participations_weekly_event_fk');
            $table->dropUnique('participations_user_context_unique');
            $table->dropUnique('participations_context_reference_unique');
            $table->dropColumn(['context_key', 'weekly_event_id']);
            $table->unique(['user_id', 'challenge_id']);
        });
        Schema::dropIfExists('weekly_events');
        Schema::dropIfExists('weekly_calendar_locks');
    }
};
