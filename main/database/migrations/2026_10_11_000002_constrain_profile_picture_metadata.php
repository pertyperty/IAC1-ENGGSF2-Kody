<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users ADD CONSTRAINT user_picture_private CHECK (profile_picture IS NULL OR (jsonb_typeof(profile_picture) = 'object' AND jsonb_exists(profile_picture, 'disk') AND jsonb_exists(profile_picture, 'path') AND profile_picture - 'disk' - 'path' = '{}'::jsonb AND jsonb_typeof(profile_picture->'disk') = 'string' AND profile_picture->>'disk' IN ('local', 's3') AND jsonb_typeof(profile_picture->'path') = 'string' AND profile_picture->>'path' ~ '^profile-pictures/[0-9a-f-]{36}\\.jpg$'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT user_picture_private');
    }
};
