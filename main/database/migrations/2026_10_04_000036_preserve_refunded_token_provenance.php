<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_lots', function (Blueprint $table): void {
            $table->bigInteger('initial_purchased_kb')->default(0);
            $table->bigInteger('remaining_purchased_kb')->default(0);
        });
        DB::table('wallet_lots')->where('kind', 'Purchase')->update(['initial_purchased_kb' => DB::raw('initial_kb'), 'remaining_purchased_kb' => DB::raw('remaining_kb')]);
        DB::statement('ALTER TABLE wallet_lots ADD CONSTRAINT wallet_origin_valid CHECK (initial_purchased_kb BETWEEN 0 AND initial_kb AND remaining_purchased_kb BETWEEN 0 AND initial_purchased_kb AND remaining_purchased_kb <= remaining_kb)');
        Schema::table('payment_purchases', fn (Blueprint $table) => $table->jsonb('fee_schedule')->default('{}'));
        DB::statement("ALTER TABLE payout_requests DROP CONSTRAINT payout_request_valid, ADD CONSTRAINT payout_request_valid CHECK (gross_minor > fee_minor AND fee_minor >= 0 AND state IN ('PendingReview','Queued','Creating','Pending','Succeeded','Failed','Rejected','Review','Reversed'))");
        foreach (['module_revisions' => [5, 100], 'course_revisions' => [20, 500], 'coding_challenge_revisions' => [5, 50]] as $table => [$min,$max]) {
            DB::statement("ALTER TABLE $table ADD CONSTRAINT {$table}_price_band CHECK ((price_kb = 0 OR price_kb BETWEEN $min AND $max) AND minimum_xp IN (0,200,600,1500,3000))");
        }
    }

    public function down(): void
    {
        if (DB::table('wallet_operations')->exists() || DB::table('payout_requests')->where('state', 'Reversed')->exists()) {
            throw new RuntimeException('Preserve purchase provenance and financial outcomes.');
        }
        foreach (['module_revisions', 'course_revisions', 'coding_challenge_revisions'] as $table) {
            DB::statement("ALTER TABLE $table DROP CONSTRAINT {$table}_price_band");
        }
        Schema::table('wallet_lots', fn (Blueprint $table) => $table->dropColumn(['initial_purchased_kb', 'remaining_purchased_kb']));
        Schema::table('payment_purchases', fn (Blueprint $table) => $table->dropColumn('fee_schedule'));
        DB::statement("ALTER TABLE payout_requests DROP CONSTRAINT payout_request_valid, ADD CONSTRAINT payout_request_valid CHECK (gross_minor > fee_minor AND fee_minor >= 0 AND state IN ('PendingReview','Queued','Creating','Pending','Succeeded','Failed','Rejected','Review'))");
    }
};
