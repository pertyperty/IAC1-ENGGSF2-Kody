<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE INDEX audit_course_enrollment_dependency ON audit_events ((context->>'course_id')) WHERE event = 'course.enrolled'");
        DB::statement("CREATE INDEX audit_challenge_submission_dependency ON audit_events ((context->>'challenge_id')) WHERE event = 'challenge.attempted'");
        DB::statement("CREATE INDEX audit_weekly_challenge_dependency ON audit_events ((context->>'challenge_id')) WHERE subject_type = 'weekly_event'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS audit_course_enrollment_dependency');
        DB::statement('DROP INDEX IF EXISTS audit_challenge_submission_dependency');
        DB::statement('DROP INDEX IF EXISTS audit_weekly_challenge_dependency');
    }
};
