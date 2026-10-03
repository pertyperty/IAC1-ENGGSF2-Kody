<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_applications', function (Blueprint $table): void {
            $table->integer('record_version')->default(1);
            $table->string('verification_notes', 255)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->index(['verification_status', 'id']);
        });
        DB::statement('ALTER TABLE instructor_applications ADD CONSTRAINT application_version_positive CHECK (record_version > 0)');
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 100);
            $table->string('subject_type', 60);
            $table->string('subject_id', 60);
            $table->jsonb('context');
            $table->timestampTz('created_at');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_id', 'created_at']);
        });
        Schema::create('creator_decision_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('instructor_application_id')->constrained()->cascadeOnDelete();
            $table->integer('review_version');
            $table->string('recipient_email', 100);
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();
            $table->unique(['instructor_application_id', 'review_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_decision_deliveries');
        Schema::dropIfExists('audit_events');
        DB::statement('ALTER TABLE instructor_applications DROP CONSTRAINT application_version_positive');
        Schema::table('instructor_applications', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['verification_status', 'id']);
            $table->dropColumn(['record_version', 'verification_notes', 'reviewed_by', 'verified_at']);
        });
    }
};
