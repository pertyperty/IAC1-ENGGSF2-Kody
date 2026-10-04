<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_webhooks', function (Blueprint $table): void {
            $table->jsonb('proof')->default('{}');
            $table->timestampTz('last_queued_at')->nullable();
        });
        Schema::table('refund_requests', function (Blueprint $table): void {
            $table->integer('fee_minor')->default(0);
            $table->integer('reserved_minor')->default(0);
        });
        DB::statement('ALTER TABLE refund_requests ADD CONSTRAINT refund_fee_valid CHECK (fee_minor >= 0 AND reserved_minor >= 0)');
        Schema::table('weekly_events', fn (Blueprint $table) => $table->unsignedSmallInteger('economy_policy_version')->default(0));
    }

    public function down(): void
    {
        if (DB::table('provider_webhooks')->exists() || DB::table('refund_requests')->exists() || DB::table('weekly_events')->where('economy_policy_version', '>', 0)->exists()) {
            throw new RuntimeException('Retained provider proofs and event policies require a compatible release.');
        }
        Schema::table('provider_webhooks', fn (Blueprint $table) => $table->dropColumn(['proof', 'last_queued_at']));
        Schema::table('refund_requests', fn (Blueprint $table) => $table->dropColumn(['fee_minor', 'reserved_minor']));
        Schema::table('weekly_events', fn (Blueprint $table) => $table->dropColumn('economy_policy_version'));
    }
};
