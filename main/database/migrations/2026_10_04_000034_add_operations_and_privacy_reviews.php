<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_budget_reports', function (Blueprint $table): void {
            $table->date('month')->primary();
            $table->bigInteger('reported_minor');
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('record_version')->default(1);
            $table->timestampTz('updated_at');
        });
        DB::statement('ALTER TABLE operations_budget_reports ADD CONSTRAINT budget_report_valid CHECK (reported_minor >= 0 AND record_version > 0)');
        Schema::create('creator_erasure_reviews', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('fingerprint', 64);
            $table->string('state', 15)->default('Pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('review_notes', 500)->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
        });
        DB::statement("CREATE UNIQUE INDEX creator_erasure_pending_unique ON creator_erasure_reviews(user_id) WHERE state = 'Pending'");
        DB::statement("ALTER TABLE creator_erasure_reviews ADD CONSTRAINT creator_erasure_review_valid CHECK (state IN ('Pending','Approved','Rejected','Consumed'))");
        Schema::create('financial_remedy_cases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('reference', 100);
            $table->string('message', 1000);
            $table->string('state', 20)->default('Open');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('review_notes', 1000)->nullable();
            $table->timestampsTz();
        });
        DB::statement("ALTER TABLE financial_remedy_cases ADD CONSTRAINT remedy_case_state CHECK (state IN ('Open','Review','Resolved'))");
        Schema::create('payout_reversals', function (Blueprint $table): void {
            $table->foreignUuid('payout_id')->primary()->constrained('payout_requests')->restrictOnDelete();
            $table->bigInteger('returned_minor');
            $table->timestampTz('created_at');
        });
        DB::statement('ALTER TABLE payout_reversals ADD CONSTRAINT payout_returned_nonnegative CHECK (returned_minor >= 0)');
    }

    public function down(): void
    {
        foreach (['payout_reversals', 'financial_remedy_cases', 'creator_erasure_reviews', 'operations_budget_reports'] as $name) {
            if (DB::table($name)->exists()) {
                throw new RuntimeException('Retained operations and privacy records require a compatible release.');
            }
        }
        foreach (['payout_reversals', 'financial_remedy_cases', 'creator_erasure_reviews', 'operations_budget_reports'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
