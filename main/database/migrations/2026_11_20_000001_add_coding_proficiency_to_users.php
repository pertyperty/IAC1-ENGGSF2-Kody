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
            $table->string('coding_proficiency', 20)->nullable();
        });
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_coding_proficiency_check CHECK (coding_proficiency IS NULL OR coding_proficiency IN ('beginner', 'intermediate', 'expert'))");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('coding_proficiency');
        });
    }
};
