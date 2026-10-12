<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_read_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('learning_modules')->restrictOnDelete();
            $table->foreignId('revision_id')->constrained('module_revisions')->restrictOnDelete();
            $table->timestampTz('completed_at');
            $table->unique(['user_id', 'module_id']);
            $table->index('completed_at');
        });
        DB::statement('ALTER TABLE module_read_completions ADD CONSTRAINT module_read_revision_owner FOREIGN KEY (revision_id, module_id) REFERENCES module_revisions(id, module_id)');
        Schema::table('email_verifications', function (Blueprint $table) {
            $table->string('otp_hash', 64)->nullable();
            $table->timestampTz('otp_expires_at')->nullable();
            $table->unsignedSmallInteger('otp_attempts')->default(0);
        });
        DB::statement('ALTER TABLE email_verifications ADD CONSTRAINT verification_otp_bounds CHECK (otp_attempts BETWEEN 0 AND 5)');
        Schema::table('verification_deliveries', fn (Blueprint $table) => $table->text('otp')->nullable());
    }

    public function down(): void
    {
        if (DB::table('module_read_completions')->exists() || DB::table('email_verifications')->whereNotNull('otp_hash')->exists()) {
            throw new RuntimeException('Preserve article completion history and pending verification codes; roll back code without reverting this migration.');
        }
        Schema::dropIfExists('module_read_completions');
        DB::statement('ALTER TABLE email_verifications DROP CONSTRAINT verification_otp_bounds');
        Schema::table('email_verifications', fn (Blueprint $table) => $table->dropColumn(['otp_hash', 'otp_expires_at', 'otp_attempts']));
        Schema::table('verification_deliveries', fn (Blueprint $table) => $table->dropColumn('otp'));
    }
};
