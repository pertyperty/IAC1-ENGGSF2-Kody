<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('content_type', 15);
            $table->unsignedBigInteger('content_id');
            $table->unsignedBigInteger('revision_id')->nullable();
            $table->string('source', 15);
            $table->foreignUuid('purchase_id')->nullable()->constrained('content_purchases')->restrictOnDelete();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'content_type', 'content_id']);
        });
        DB::statement("ALTER TABLE content_entitlements ADD CONSTRAINT content_entitlement_valid CHECK (content_type IN ('module','course','challenge') AND source IN ('Legacy','Free','Paid') AND ((source = 'Paid' AND purchase_id IS NOT NULL) OR (source <> 'Paid' AND purchase_id IS NULL)))");
        DB::statement("INSERT INTO content_entitlements(user_id,content_type,content_id,source,created_at,updated_at)
            SELECT user_id,kind,COALESCE(module_id,course_id,challenge_id),'Legacy',first_opened_at,first_opened_at FROM content_accesses
            ON CONFLICT(user_id,content_type,content_id) DO NOTHING");
        DB::statement("INSERT INTO content_entitlements(user_id,content_type,content_id,revision_id,source,created_at,updated_at)
            SELECT user_id,'course',course_id,course_revision_id,'Legacy',enrolled_at,enrolled_at FROM course_enrollments
            ON CONFLICT(user_id,content_type,content_id) DO NOTHING");
        DB::statement("INSERT INTO content_entitlements(user_id,content_type,content_id,source,created_at,updated_at)
            SELECT user_id,'challenge',challenge_id,'Legacy',created_at,updated_at FROM challenge_participations WHERE weekly_event_id IS NULL
            ON CONFLICT(user_id,content_type,content_id) DO NOTHING");
    }

    public function down(): void
    {
        if (DB::table('content_entitlements')->where('source', '<>', 'Legacy')->exists()) {
            throw new RuntimeException('Preserve granted access and refund history; use a compatible release.');
        }
        Schema::dropIfExists('content_entitlements');
    }
};
