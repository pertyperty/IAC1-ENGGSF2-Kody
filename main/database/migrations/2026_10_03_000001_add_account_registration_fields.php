<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Existing skeleton identities need an explicit later profile backfill.
            $table->string('username', 30)->nullable()->unique();
            $table->string('first_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->string('account_role', 20)->default('Learner');
            $table->string('account_status', 20)->default('Unverified');
        });

        DB::statement("UPDATE users SET account_status = 'Active' WHERE email_verified_at IS NOT NULL");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_valid CHECK (account_role IN ('Learner', 'Contributor', 'Instructor', 'Moderator', 'Admin'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_valid CHECK (account_status IN ('Unverified', 'Active', 'Suspended', 'Archived', 'Deleted'))");
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_username_length CHECK (username IS NULL OR char_length(username) BETWEEN 6 AND 30)');
        DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (LOWER(email))');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_registered_profile_valid CHECK (username IS NULL OR (first_name IS NOT NULL AND last_name IS NOT NULL AND char_length(first_name) BETWEEN 1 AND 50 AND char_length(last_name) BETWEEN 1 AND 50 AND char_length(email) <= 100))');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_activation_verified CHECK (account_status <> 'Active' OR email_verified_at IS NOT NULL)");

        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email', 100);
            $table->char('token_hash', 64)->nullable()->unique();
            $table->timestampTz('expires_at')->nullable();
            $table->unsignedSmallInteger('request_count')->default(0);
            $table->timestampTz('last_requested_at')->nullable();
            $table->timestampsTz();
        });
        DB::statement('ALTER TABLE email_verifications ADD CONSTRAINT verification_request_count_valid CHECK (request_count BETWEEN 0 AND 5)');

        Schema::create('verification_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64);
            $table->text('token')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'sent_at', 'cancelled_at']);
        });

        Schema::create('instructor_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('institution_name', 100);
            $table->string('specialization', 100);
            $table->string('credential_disk');
            $table->string('credential_path');
            $table->string('verification_status', 20)->default('Pending');
            $table->timestampsTz();
        });
        DB::statement("ALTER TABLE instructor_applications ADD CONSTRAINT instructor_application_status_valid CHECK (verification_status IN ('Pending', 'Approved', 'Rejected'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_applications');
        Schema::dropIfExists('verification_deliveries');
        Schema::dropIfExists('email_verifications');
        DB::statement('DROP INDEX users_email_lower_unique');
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_valid, DROP CONSTRAINT users_status_valid, DROP CONSTRAINT users_username_length, DROP CONSTRAINT users_registered_profile_valid, DROP CONSTRAINT users_activation_verified');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'first_name', 'last_name', 'account_role', 'account_status']);
        });
    }
};
