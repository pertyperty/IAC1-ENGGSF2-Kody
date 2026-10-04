<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economy_locks', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->bigInteger('platform_minor')->default(0);
            $table->bigInteger('reserved_minor')->default(0);
        });
        DB::table('economy_locks')->insert(['id' => 1]);
        DB::statement('ALTER TABLE economy_locks ADD CONSTRAINT economy_cash_valid CHECK (platform_minor >= reserved_minor AND reserved_minor >= 0)');
        Schema::create('wallet_accounts', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->restrictOnDelete();
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('reserved')->default(0);
        });
        DB::statement('ALTER TABLE wallet_accounts ADD CONSTRAINT wallet_nonnegative CHECK (balance >= reserved AND reserved >= 0)');
        Schema::create('wallet_operations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('kind', 30);
            $table->string('reference', 150)->unique();
            $table->bigInteger('kodebits');
            $table->bigInteger('value_minor');
            $table->unsignedSmallInteger('policy_version')->default(1);
            $table->timestampTz('created_at')->index();
        });
        Schema::create('wallet_lots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('operation_id')->unique()->constrained('wallet_operations')->restrictOnDelete();
            $table->string('kind', 20);
            $table->bigInteger('initial_kb');
            $table->bigInteger('remaining_kb');
            $table->bigInteger('reserved_kb')->default(0);
            $table->bigInteger('initial_minor');
            $table->bigInteger('remaining_minor');
            $table->timestampTz('created_at');
            $table->index(['user_id', 'created_at', 'id']);
        });
        DB::statement("ALTER TABLE wallet_lots ADD CONSTRAINT wallet_lot_valid CHECK (kind IN ('Purchase','Reward','Creator','Refund') AND initial_kb > 0 AND remaining_kb BETWEEN 0 AND initial_kb AND reserved_kb BETWEEN 0 AND remaining_kb AND initial_minor >= 0 AND remaining_minor BETWEEN 0 AND initial_minor AND (remaining_kb > 0 OR remaining_minor = 0))");
        Schema::create('wallet_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('operation_id')->constrained('wallet_operations')->restrictOnDelete();
            $table->foreignUuid('lot_id')->constrained('wallet_lots')->restrictOnDelete();
            $table->bigInteger('kodebits');
            $table->bigInteger('value_minor');
            $table->timestampTz('created_at');
            $table->unique(['operation_id', 'lot_id']);
        });
        DB::statement('ALTER TABLE wallet_entries ADD CONSTRAINT wallet_entry_nonzero CHECK (kodebits <> 0)');
        Schema::create('platform_cash_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference', 150)->unique();
            $table->bigInteger('amount_minor');
            $table->timestampTz('created_at');
        });
        Schema::create('content_purchases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('operation_id')->unique()->constrained('wallet_operations')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('creator_id')->constrained('users')->restrictOnDelete();
            $table->string('content_type', 15);
            $table->unsignedBigInteger('content_id');
            $table->unsignedBigInteger('revision_id');
            $table->string('status', 15)->default('Active');
            $table->bigInteger('kodebits');
            $table->bigInteger('purchased_kb');
            $table->bigInteger('value_minor');
            $table->bigInteger('creator_minor');
            $table->bigInteger('platform_minor');
            $table->timestampTz('created_at')->index();
            $table->timestampTz('refunded_at')->nullable();
        });
        DB::statement("CREATE UNIQUE INDEX content_purchase_active_unique ON content_purchases (user_id,content_type,content_id) WHERE status = 'Active'");
        DB::statement("ALTER TABLE content_purchases ADD CONSTRAINT content_purchase_valid CHECK (content_type IN ('module','course','challenge') AND status IN ('Active','Refunded') AND kodebits > 0 AND purchased_kb BETWEEN 0 AND kodebits AND value_minor >= 0 AND creator_minor >= 0 AND platform_minor >= 0 AND creator_minor + platform_minor = value_minor AND user_id <> creator_id)");
        Schema::create('publisher_earnings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_id')->unique()->constrained('content_purchases')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('kind', 15);
            $table->bigInteger('amount_minor');
            $table->bigInteger('kb_milli')->default(0);
            $table->bigInteger('claimed_minor')->default(0);
            $table->bigInteger('claimed_kb_milli')->default(0);
            $table->bigInteger('reserved_minor')->default(0);
            $table->string('status', 15)->default('Available');
            $table->timestampTz('available_at')->index();
            $table->timestampTz('created_at');
            $table->index(['user_id', 'status', 'available_at']);
        });
        DB::statement("ALTER TABLE publisher_earnings ADD CONSTRAINT publisher_earning_valid CHECK (kind IN ('Cash','KodeBits') AND status IN ('Available','Reversed') AND amount_minor >= 0 AND claimed_minor >= 0 AND reserved_minor >= 0 AND claimed_minor + reserved_minor <= amount_minor AND kb_milli >= 0 AND claimed_kb_milli BETWEEN 0 AND kb_milli)");
        Schema::create('payment_purchases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('confirmation_id');
            $table->string('package', 20);
            $table->integer('kodebits');
            $table->integer('gross_minor');
            $table->integer('fee_minor');
            $table->string('currency', 3)->default('PHP');
            $table->string('channel', 20)->default('GCASH');
            $table->string('state', 20)->default('Queued');
            $table->string('provider_request_id', 100)->nullable()->unique();
            $table->string('provider_payment_id', 100)->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->foreignUuid('lot_id')->nullable()->constrained('wallet_lots')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['user_id', 'confirmation_id']);
        });
        DB::statement("ALTER TABLE payment_purchases ADD CONSTRAINT payment_purchase_valid CHECK (kodebits > 0 AND gross_minor > fee_minor AND fee_minor >= 0 AND currency = 'PHP' AND channel = 'GCASH' AND state IN ('Queued','Creating','Pending','Succeeded','Failed','Review','Refunding','Refunded'))");
        Schema::create('payout_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('confirmation_id');
            $table->bigInteger('gross_minor');
            $table->integer('fee_minor');
            $table->string('state', 20)->default('PendingReview');
            $table->text('recipient');
            $table->string('provider_id', 100)->nullable()->unique();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('review_notes', 500)->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'confirmation_id']);
        });
        DB::statement("ALTER TABLE payout_requests ADD CONSTRAINT payout_request_valid CHECK (gross_minor > fee_minor AND fee_minor >= 0 AND state IN ('PendingReview','Queued','Creating','Pending','Succeeded','Failed','Rejected','Review'))");
        Schema::create('payout_allocations', function (Blueprint $table): void {
            $table->foreignUuid('payout_id')->constrained('payout_requests')->restrictOnDelete();
            $table->foreignUuid('earning_id')->constrained('publisher_earnings')->restrictOnDelete();
            $table->bigInteger('amount_minor');
            $table->primary(['payout_id', 'earning_id']);
        });
        Schema::create('refund_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('kind', 15);
            $table->uuid('target_id');
            $table->string('state', 20)->default('PendingReview');
            $table->string('reason', 500);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('review_notes', 500)->nullable();
            $table->string('provider_id', 100)->nullable()->unique();
            $table->timestampsTz();
        });
        DB::statement("CREATE UNIQUE INDEX refund_open_unique ON refund_requests (kind,target_id) WHERE state NOT IN ('Failed','Rejected')");
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT refund_request_valid CHECK (kind IN ('Purchase','Access') AND state IN ('PendingReview','Queued','Creating','Pending','Succeeded','Failed','Rejected','Review'))");
        Schema::create('provider_webhooks', function (Blueprint $table): void {
            $table->string('id', 100)->primary();
            $table->string('event', 60);
            $table->string('object_id', 100);
            $table->string('digest', 64);
            $table->string('state', 20)->default('Pending');
            $table->timestampTz('created_at');
            $table->timestampTz('processed_at')->nullable();
        });
        Schema::create('financial_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('reference', 150)->unique();
            $table->string('title', 100);
            $table->jsonb('details');
            $table->string('state', 15)->default('Pending')->index();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('attempted_at')->nullable();
            $table->timestampTz('lease_expires_at')->nullable();
            $table->timestampsTz();
        });
        Schema::create('xp_totals', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->restrictOnDelete();
            $table->bigInteger('xp')->default(0);
        });
        DB::statement('ALTER TABLE xp_totals ADD CONSTRAINT xp_total_nonnegative CHECK (xp >= 0)');
        Schema::create('xp_awards', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('reference', 150);
            $table->integer('xp');
            $table->timestampTz('created_at')->index();
            $table->unique(['user_id', 'reference']);
        });
        DB::statement('ALTER TABLE xp_awards ADD CONSTRAINT xp_award_positive CHECK (xp > 0)');
        Schema::create('reward_months', function (Blueprint $table): void {
            $table->date('month')->primary();
            $table->bigInteger('issued_kb')->default(0);
        });
        DB::statement('ALTER TABLE reward_months ADD CONSTRAINT reward_issued_nonnegative CHECK (issued_kb >= 0)');
        Schema::create('weekly_result_sets', function (Blueprint $table): void {
            $table->foreignId('event_id')->primary()->constrained('weekly_events')->restrictOnDelete();
            $table->string('status', 15)->default('Draft');
            $table->foreignId('published_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('created_at');
        });
        Schema::create('weekly_results', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained('weekly_result_sets', 'event_id')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('submission_id')->nullable()->constrained('challenge_submissions')->nullOnDelete();
            $table->integer('score');
            $table->integer('rank');
            $table->integer('reward_kb')->default(0);
            $table->primary(['event_id', 'user_id']);
        });
        DB::statement('ALTER TABLE weekly_results ADD CONSTRAINT weekly_result_valid CHECK (score BETWEEN 0 AND 10000 AND rank > 0 AND reward_kb >= 0)');
        foreach (['module_revisions', 'course_revisions', 'coding_challenge_revisions'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->integer('price_kb')->default(0);
                $table->integer('minimum_xp')->default(0);
                $table->jsonb('prerequisite_modules')->default('[]');
                $table->string('creator_settlement', 15)->default('Cash');
            });
            DB::statement("ALTER TABLE $name ADD CONSTRAINT {$name}_access_valid CHECK (price_kb >= 0 AND minimum_xp >= 0 AND jsonb_typeof(prerequisite_modules) = 'array' AND jsonb_array_length(prerequisite_modules) <= 5 AND creator_settlement IN ('Cash','KodeBits'))");
        }
        DB::unprepared("CREATE OR REPLACE FUNCTION prevent_ledger_mutation() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Ledger rows are append-only'; END; $$");
        foreach (['wallet_operations', 'wallet_entries', 'platform_cash_entries'] as $name) {
            DB::statement("CREATE TRIGGER {$name}_immutable BEFORE UPDATE OR DELETE ON $name FOR EACH ROW EXECUTE FUNCTION prevent_ledger_mutation()");
        }
    }

    public function down(): void
    {
        foreach (['wallet_operations', 'xp_awards', 'payment_purchases', 'payout_requests', 'platform_cash_entries',
            'refund_requests', 'financial_deliveries', 'provider_webhooks', 'weekly_result_sets', 'reward_months'] as $history) {
            if (DB::table($history)->exists()) {
                throw new RuntimeException('Preserve financial and achievement history; deploy a compatible release.');
            }
        }
        foreach (['module_revisions', 'course_revisions', 'coding_challenge_revisions'] as $name) {
            if (DB::table($name)->where('price_kb', '<>', 0)->orWhere('minimum_xp', '<>', 0)
                ->orWhereRaw("prerequisite_modules <> '[]'::jsonb")->exists()) {
                throw new RuntimeException('Preserve reviewed access settings; deploy a compatible release.');
            }
        }
        foreach (['module_revisions', 'course_revisions', 'coding_challenge_revisions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['price_kb', 'minimum_xp', 'prerequisite_modules', 'creator_settlement']));
        }
        foreach (['weekly_results', 'weekly_result_sets', 'reward_months', 'xp_awards', 'xp_totals', 'financial_deliveries', 'provider_webhooks', 'refund_requests', 'payout_allocations', 'payout_requests', 'payment_purchases', 'publisher_earnings', 'content_purchases', 'platform_cash_entries', 'wallet_entries', 'wallet_lots', 'wallet_operations', 'wallet_accounts', 'economy_locks'] as $name) {
            Schema::dropIfExists($name);
        }
        DB::statement('DROP FUNCTION prevent_ledger_mutation()');
    }
};
