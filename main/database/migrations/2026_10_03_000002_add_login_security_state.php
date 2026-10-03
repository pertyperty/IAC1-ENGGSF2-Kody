<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestampTz('login_locked_until')->nullable();
            $table->timestampTz('last_login_at')->nullable();
            $table->char('active_session_hash', 64)->nullable();
            $table->timestampTz('active_session_expires_at')->nullable();
        });
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_login_failures_nonnegative CHECK (failed_login_attempts >= 0)');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_active_session_complete CHECK ((active_session_hash IS NULL) = (active_session_expires_at IS NULL))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_login_failures_nonnegative, DROP CONSTRAINT users_active_session_complete');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn([
            'failed_login_attempts', 'login_locked_until', 'last_login_at',
            'active_session_hash', 'active_session_expires_at',
        ]));
    }
};
