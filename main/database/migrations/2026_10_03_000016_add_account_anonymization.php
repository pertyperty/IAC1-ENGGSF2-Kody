<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->timestampTz('anonymized_at')->nullable());
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_anonymized_identity_check CHECK (anonymized_at IS NULL OR (account_status = 'Deleted' AND name = 'Deleted account' AND username IS NULL AND first_name IS NULL AND last_name IS NULL AND email_verified_at IS NULL AND active_session_hash IS NULL AND active_session_expires_at IS NULL AND remember_token IS NULL))");
        Schema::create('account_file_erasures', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('disk');
            $table->char('path_digest', 64);
            $table->text('path')->nullable();
            $table->integer('attempts')->default(0);
            $table->unsignedBigInteger('queued_job_id')->nullable();
            $table->timestampTz('last_queued_at');
            $table->timestampTz('failed_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'disk', 'path_digest']);
            $table->index(['completed_at', 'last_queued_at']);
        });
        DB::statement('ALTER TABLE account_file_erasures ADD CONSTRAINT erasure_state_check CHECK (attempts >= 0 AND ((completed_at IS NULL) = (path IS NOT NULL)))');
    }

    public function down(): void
    {
        if (DB::table('users')->whereNotNull('anonymized_at')->exists()
            || DB::table('account_file_erasures')->whereNull('completed_at')->exists()) {
            throw new RuntimeException('Account erasure records must be preserved. Roll back application code without reverting this migration.');
        }
        Schema::dropIfExists('account_file_erasures');
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_anonymized_identity_check');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('anonymized_at'));
    }
};
