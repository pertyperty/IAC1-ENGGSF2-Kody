<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('subject_hash', 64)->nullable()->unique();
            $table->integer('record_version');
            $table->timestampTz('linked_at')->nullable();
            $table->timestampsTz();
        });
        Schema::create('google_auth_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('session_hash', 64)->unique();
            $table->char('state_hash', 64)->unique();
            $table->string('intent', 8);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('profile_version')->nullable();
            $table->integer('identity_version')->nullable();
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampTz('created_at');
        });
        DB::statement("ALTER TABLE google_identities ADD CONSTRAINT google_identity_valid CHECK (record_version > 0 AND (subject_hash IS NULL) = (linked_at IS NULL) AND (subject_hash IS NULL OR subject_hash ~ '^[a-f0-9]{64}$'))");
        DB::statement("ALTER TABLE google_auth_attempts ADD CONSTRAINT google_attempt_valid CHECK (session_hash ~ '^[a-f0-9]{64}$' AND state_hash ~ '^[a-f0-9]{64}$' AND expires_at > created_at AND ((intent = 'login' AND user_id IS NULL AND profile_version IS NULL AND identity_version IS NULL) OR (intent = 'link' AND user_id IS NOT NULL AND profile_version IS NOT NULL AND identity_version IS NOT NULL AND profile_version > 0 AND identity_version >= 0)))");
    }

    public function down(): void
    {
        Schema::dropIfExists('google_auth_attempts');
        Schema::dropIfExists('google_identities');
    }
};
