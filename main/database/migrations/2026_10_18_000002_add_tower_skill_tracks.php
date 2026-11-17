<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tower_levels', function (Blueprint $table) {
            // NULL preserves the historical position-based track, including custom floors.
            $table->string('skill_track', 20)->nullable();
        });
        DB::statement("ALTER TABLE tower_levels ADD CONSTRAINT tower_skill_track_valid CHECK (skill_track IS NULL OR skill_track IN ('beginner', 'intermediate', 'expert'))");
    }

    public function down(): void
    {
        if (DB::table('tower_levels')->whereNotNull('skill_track')->exists()) {
            throw new RuntimeException('Explicit Tower tracks exist. Restore a reviewed backup instead of silently moving their progress gates.');
        }
        Schema::table('tower_levels', fn (Blueprint $table) => $table->dropColumn('skill_track'));
    }
};
