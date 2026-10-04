<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_attempt_scores', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')->nullable()->constrained('challenge_submissions')->nullOnDelete();
            $table->foreignId('event_id')->constrained('weekly_events')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('score');
            $table->timestampTz('completed_at');
            $table->index(['event_id', 'user_id', 'score']);
        });
        DB::statement('ALTER TABLE weekly_attempt_scores ADD CONSTRAINT weekly_score_range CHECK (score BETWEEN 0 AND 10000)');
        DB::statement("ALTER TABLE weekly_events DROP CONSTRAINT weekly_events_values_check, ADD CONSTRAINT weekly_events_values_check CHECK (status IN ('Scheduled','Active','Ended','Unavailable') AND record_version > 0 AND reward_mode IN ('Deferred','Capped'))");
        DB::statement("ALTER TABLE weekly_result_sets ADD CONSTRAINT result_set_state CHECK ((status = 'Draft' AND published_by IS NULL AND published_at IS NULL) OR (status = 'Published' AND published_by IS NOT NULL AND published_at IS NOT NULL))");
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION protect_weekly_result() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        IF TG_TABLE_NAME = 'weekly_results' AND EXISTS (SELECT 1 FROM weekly_result_sets WHERE event_id = NEW.event_id AND status = 'Published') THEN
            RAISE EXCEPTION 'Published results are immutable';
        END IF;
        RETURN NEW;
    END IF;
    IF TG_TABLE_NAME = 'weekly_result_sets' THEN
        IF OLD.status = 'Published' THEN RAISE EXCEPTION 'Published results are immutable'; END IF;
    ELSIF TG_TABLE_NAME = 'weekly_attempt_scores' OR EXISTS (SELECT 1 FROM weekly_result_sets WHERE event_id = OLD.event_id AND status = 'Published') THEN
        IF TG_OP = 'UPDATE' THEN
            IF NEW.submission_id IS NULL AND (to_jsonb(NEW) - 'submission_id') = (to_jsonb(OLD) - 'submission_id') THEN RETURN NEW; END IF;
        END IF;
        RAISE EXCEPTION 'Verified scores are immutable';
    END IF;
    IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
    RETURN NEW;
END; $$;
SQL);
        foreach (['weekly_result_sets', 'weekly_results', 'weekly_attempt_scores'] as $table) {
            DB::statement("CREATE TRIGGER {$table}_protected BEFORE INSERT OR UPDATE OR DELETE ON $table FOR EACH ROW EXECUTE FUNCTION protect_weekly_result()");
        }
    }

    public function down(): void
    {
        if (DB::table('weekly_attempt_scores')->exists() || DB::table('weekly_result_sets')->exists() || DB::table('weekly_events')->where('reward_mode', 'Capped')->exists()) {
            throw new RuntimeException('Retain verified weekly outcomes and reward policies.');
        }
        foreach (['weekly_result_sets', 'weekly_results'] as $table) {
            DB::statement("DROP TRIGGER {$table}_protected ON $table");
        }
        Schema::drop('weekly_attempt_scores');
        DB::statement('DROP FUNCTION protect_weekly_result()');
        DB::statement('ALTER TABLE weekly_result_sets DROP CONSTRAINT result_set_state');
        DB::statement("ALTER TABLE weekly_events DROP CONSTRAINT weekly_events_values_check, ADD CONSTRAINT weekly_events_values_check CHECK (status IN ('Scheduled','Active','Ended','Unavailable') AND record_version > 0 AND reward_mode = 'Deferred')");
    }
};
