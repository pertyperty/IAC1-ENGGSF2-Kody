<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coding_challenge_revisions', fn (Blueprint $table) => $table->text('starter_code')->nullable());
        DB::statement('ALTER TABLE coding_challenge_revisions ADD CONSTRAINT challenge_starter_size CHECK (octet_length(starter_code) <= 65536)');
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION preserve_challenge_starter_code() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Challenge revision starter code is immutable';
            END;
            $$;
            CREATE TRIGGER challenge_starter_immutable BEFORE UPDATE OF starter_code ON coding_challenge_revisions
            FOR EACH ROW WHEN (OLD.starter_code IS DISTINCT FROM NEW.starter_code)
            EXECUTE FUNCTION preserve_challenge_starter_code();
            SQL);
    }

    public function down(): void
    {
        DB::statement('LOCK TABLE coding_challenge_revisions IN ACCESS EXCLUSIVE MODE');
        if (DB::table('coding_challenge_revisions')->whereNotNull('starter_code')->exists()) {
            throw new RuntimeException('Retained challenge starter code prevents destructive rollback. Keep the additive schema during application rollback.');
        }
        DB::statement('DROP TRIGGER challenge_starter_immutable ON coding_challenge_revisions');
        DB::statement('DROP FUNCTION preserve_challenge_starter_code()');
        DB::statement('ALTER TABLE coding_challenge_revisions DROP CONSTRAINT challenge_starter_size');
        Schema::table('coding_challenge_revisions', fn (Blueprint $table) => $table->dropColumn('starter_code'));
    }
};
