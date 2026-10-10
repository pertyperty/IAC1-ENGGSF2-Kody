<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_revisions', fn (Blueprint $table) => $table->jsonb('attachments')->default('[]'));
        DB::statement("ALTER TABLE module_revisions ADD CONSTRAINT module_attachments_valid CHECK (jsonb_typeof(attachments) = 'array' AND jsonb_array_length(attachments) <= 5)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE module_revisions DROP CONSTRAINT module_attachments_valid');
        Schema::table('module_revisions', fn (Blueprint $table) => $table->dropColumn('attachments'));
    }
};
