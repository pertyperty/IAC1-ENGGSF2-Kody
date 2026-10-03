<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_support_corrections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->integer('profile_version');
            $table->jsonb('fields');
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'profile_version']);
        });
        DB::statement("ALTER TABLE account_support_corrections ADD CONSTRAINT support_correction_check CHECK (actor_id <> user_id AND profile_version > 1 AND jsonb_typeof(fields) = 'array' AND jsonb_array_length(fields) BETWEEN 1 AND 3 AND fields <@ '[\"username\",\"first_name\",\"last_name\"]'::jsonb)");
    }

    public function down(): void
    {
        if (DB::table('account_support_corrections')->exists()) {
            throw new RuntimeException('Preserve account support correction history; roll back application code without reverting this migration.');
        }
        Schema::dropIfExists('account_support_corrections');
    }
};
