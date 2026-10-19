<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_registered_profile_valid');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_registered_profile_valid CHECK (username IS NULL OR ((first_name IS NULL OR char_length(first_name) BETWEEN 1 AND 50) AND (last_name IS NULL OR char_length(last_name) BETWEEN 1 AND 50) AND char_length(email) <= 100))');
    }

    public function down(): void
    {
        if (DB::table('users')->whereNotNull('username')->where(fn ($query) => $query->whereNull('first_name')->orWhereNull('last_name'))->exists()) {
            throw new RuntimeException('Optional identities exist. Preserve them and deploy a compatible release; never fabricate names.');
        }
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_registered_profile_valid');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_registered_profile_valid CHECK (username IS NULL OR (first_name IS NOT NULL AND last_name IS NOT NULL AND char_length(first_name) BETWEEN 1 AND 50 AND char_length(last_name) BETWEEN 1 AND 50 AND char_length(email) <= 100))');
    }
};
