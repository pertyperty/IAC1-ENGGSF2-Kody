<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coding_challenge_revisions', function (Blueprint $table): void {
            $table->string('category', 32)->default('foundations')->index();
            $table->jsonb('tags')->default('[]');
        });
        DB::statement("ALTER TABLE coding_challenge_revisions ADD CONSTRAINT challenge_category_valid CHECK (category IN ('foundations', 'numbers', 'collections', 'strings'))");
        DB::statement("ALTER TABLE coding_challenge_revisions ADD CONSTRAINT challenge_tags_valid CHECK (jsonb_typeof(tags) = 'array' AND jsonb_array_length(tags) <= 5 AND tags <@ '[\"variables\",\"conditions\",\"loops\",\"functions\",\"arrays\",\"strings\"]'::jsonb)");
        DB::statement('CREATE INDEX challenge_tags_gin ON coding_challenge_revisions USING gin (tags)');
    }

    public function down(): void
    {
        // Laravel runs PostgreSQL migrations transactionally; block concurrent metadata writes during the recheck.
        DB::statement('LOCK TABLE coding_challenge_revisions IN ACCESS EXCLUSIVE MODE');
        if (DB::table('coding_challenge_revisions')->where('category', '<>', 'foundations')->orWhereRaw("tags <> '[]'::jsonb")->exists()) {
            throw new RuntimeException('Retained challenge discovery metadata prevents destructive rollback. Keep the additive schema during application rollback.');
        }
        DB::statement('DROP INDEX IF EXISTS challenge_tags_gin');
        DB::statement('ALTER TABLE coding_challenge_revisions DROP CONSTRAINT IF EXISTS challenge_category_valid, DROP CONSTRAINT IF EXISTS challenge_tags_valid');
        Schema::table('coding_challenge_revisions', fn (Blueprint $table) => $table->dropColumn(['category', 'tags']));
    }
};
