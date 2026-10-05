<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_deliveries', fn (Blueprint $table) => $table->timestampTz('last_queued_at')->nullable());
        DB::statement('CREATE TRIGGER payout_reversals_immutable BEFORE UPDATE OR DELETE ON payout_reversals FOR EACH ROW EXECUTE FUNCTION prevent_ledger_mutation()');
    }

    public function down(): void
    {
        if (DB::table('financial_deliveries')->exists() || DB::table('payout_reversals')->exists()) {
            throw new RuntimeException('Preserve financial recovery evidence.');
        }
        DB::statement('DROP TRIGGER payout_reversals_immutable ON payout_reversals');
        Schema::table('financial_deliveries', fn (Blueprint $table) => $table->dropColumn('last_queued_at'));
    }
};
