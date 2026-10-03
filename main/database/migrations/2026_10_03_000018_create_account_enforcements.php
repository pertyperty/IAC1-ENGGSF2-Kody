<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_enforcements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 12);
            $table->integer('profile_version');
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'profile_version']);
        });
        DB::statement("ALTER TABLE account_enforcements ADD CONSTRAINT account_enforcement_check CHECK (actor_id <> user_id AND profile_version > 1 AND action IN ('Suspended','Reinstated'))");
    }

    public function down(): void
    {
        if (DB::table('account_enforcements')->exists()) {
            throw new RuntimeException('Preserve account enforcement history; roll back application code without reverting this migration.');
        }
        Schema::dropIfExists('account_enforcements');
    }
};
