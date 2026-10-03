<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributor_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('request_message', 500);
            $table->string('portfolio_link')->nullable();
            $table->string('credential_disk');
            $table->string('credential_path');
            $table->integer('account_age_days');
            $table->integer('completed_modules_count');
            $table->integer('completed_challenges_count');
            $table->string('approval_status', 12)->default('Pending');
            $table->string('moderator_feedback', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->integer('record_version')->default(1);
            $table->timestampsTz();
            $table->index(['user_id', 'id']);
            $table->index(['approval_status', 'id']);
        });
        DB::statement("CREATE UNIQUE INDEX contributor_one_pending ON contributor_applications(user_id) WHERE approval_status = 'Pending'");
        DB::statement("ALTER TABLE contributor_applications ADD CONSTRAINT contributor_state_check CHECK (record_version >= 1 AND account_age_days >= 30 AND completed_modules_count >= 25 AND completed_challenges_count >= 50 AND approval_status IN ('Pending','Approved','Rejected') AND ((approval_status = 'Pending' AND reviewed_at IS NULL AND reviewed_by IS NULL) OR (approval_status <> 'Pending' AND reviewed_at IS NOT NULL AND reviewed_by IS NOT NULL)))");
        Schema::create('contributor_notice_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('application_id')->constrained('contributor_applications')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->string('kind', 12);
            $table->timestampTz('notified_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();
            $table->unique(['application_id', 'recipient_id', 'kind']);
        });
        DB::statement("ALTER TABLE contributor_notice_deliveries ADD CONSTRAINT contributor_notice_kind_check CHECK (kind IN ('Submitted','Approved','Rejected'))");
    }

    public function down(): void
    {
        if (DB::table('contributor_applications')->exists()) {
            throw new RuntimeException('Preserve Contributor application history; roll back application code without reverting this migration.');
        }
        Schema::dropIfExists('contributor_notice_deliveries');
        Schema::dropIfExists('contributor_applications');
    }
};
