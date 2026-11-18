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
            $table->string('terms_version', 20)->nullable();
            $table->timestampTz('terms_accepted_at')->nullable();
        });
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_terms_acceptance_pair CHECK ((terms_version IS NULL) = (terms_accepted_at IS NULL))');
    }

    public function down(): void
    {
        if (DB::table('users')->whereNotNull('terms_accepted_at')->exists()) {
            throw new RuntimeException('Retained terms acceptance must not be discarded.');
        }
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['terms_version', 'terms_accepted_at']);
        });
    }
};
