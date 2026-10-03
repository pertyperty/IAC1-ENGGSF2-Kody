<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_deliveries', fn (Blueprint $table) => $table->string('purpose', 20)->default('verification'));
        DB::statement("ALTER TABLE verification_deliveries ADD CONSTRAINT delivery_purpose_valid CHECK (purpose IN ('verification', 'recovery'))");
        Schema::create('account_recoveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email', 100);
            $table->char('password_digest', 64);
            $table->char('token_hash', 64)->nullable()->unique();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('last_requested_at');
            $table->timestampTz('window_started_at');
            $table->unsignedInteger('request_count');
            $table->timestampsTz();
        });
        DB::statement('ALTER TABLE account_recoveries ADD CONSTRAINT recovery_request_count_positive CHECK (request_count > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('account_recoveries');
        DB::statement('ALTER TABLE verification_deliveries DROP CONSTRAINT delivery_purpose_valid');
        Schema::table('verification_deliveries', fn (Blueprint $table) => $table->dropColumn('purpose'));
    }
};
