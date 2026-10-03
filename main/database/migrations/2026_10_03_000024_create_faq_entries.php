<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('question', 255);
            $table->text('answer');
            $table->string('category', 30);
            $table->string('status', 10)->default('Active');
            $table->integer('record_version')->default(1);
            $table->timestampsTz();
            $table->index(['status', 'category', 'id']);
        });
        DB::statement("CREATE UNIQUE INDEX faq_question_unique ON faq_entries (lower(btrim(question))) WHERE status <> 'Deleted'");
        DB::statement("ALTER TABLE faq_entries ADD CONSTRAINT faq_entry_check CHECK (record_version > 0 AND status IN ('Active','Archived','Deleted') AND category IN ('getting-started','accounts','playing-learning','creating-content') AND char_length(btrim(question)) > 0 AND char_length(btrim(answer)) > 0)");
    }

    public function down(): void
    {
        if (DB::table('faq_entries')->exists()) {
            throw new RuntimeException('Preserve FAQ records and audit references; do not revert this migration after authoring.');
        }
        Schema::dropIfExists('faq_entries');
    }
};
