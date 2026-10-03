<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('moderator_prior_role', 20)->nullable());
        DB::statement("ALTER TABLE users ADD CONSTRAINT moderator_prior_role_check CHECK (moderator_prior_role IS NULL OR (account_role = 'Moderator' AND moderator_prior_role IN ('Learner','Contributor','Instructor')))");
        Schema::create('account_role_changes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 12);
            $table->string('previous_role', 20);
            $table->string('resulting_role', 20);
            $table->integer('profile_version');
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'profile_version']);
        });
        DB::statement("ALTER TABLE account_role_changes ADD CONSTRAINT moderator_transition_check CHECK (actor_id <> user_id AND profile_version > 1 AND ((action = 'Appointed' AND previous_role IN ('Learner','Contributor','Instructor') AND resulting_role = 'Moderator') OR (action = 'Removed' AND previous_role = 'Moderator' AND resulting_role IN ('Learner','Contributor','Instructor'))))");
    }

    public function down(): void
    {
        if (DB::table('account_role_changes')->exists() || DB::table('users')->whereNotNull('moderator_prior_role')->exists()) {
            throw new RuntimeException('Preserve Moderator appointment history and prior roles; roll back application code without reverting this migration.');
        }
        Schema::dropIfExists('account_role_changes');
        DB::statement('ALTER TABLE users DROP CONSTRAINT moderator_prior_role_check');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('moderator_prior_role'));
    }
};
