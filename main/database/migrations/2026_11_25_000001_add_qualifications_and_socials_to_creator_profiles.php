<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creator_profiles', function (Blueprint $table): void {
            $table->text('qualifications')->nullable()->after('accomplishments');
            $table->json('social_links')->nullable()->after('qualifications');
        });
        DB::statement('ALTER TABLE creator_profiles ADD CONSTRAINT creator_profile_qualifications_length CHECK (qualifications IS NULL OR char_length(qualifications) <= 1000)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE creator_profiles DROP CONSTRAINT IF EXISTS creator_profile_qualifications_length');
        Schema::table('creator_profiles', function (Blueprint $table): void {
            $table->dropColumn(['qualifications', 'social_links']);
        });
    }
};
