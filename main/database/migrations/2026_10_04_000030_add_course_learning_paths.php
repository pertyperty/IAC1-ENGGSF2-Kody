<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_revisions', function (Blueprint $table): void {
            $table->boolean('sequential')->default(false);
        });
        // Existing enrollments retain open access. New enrollments snapshot the reviewed policy.
        Schema::table('course_enrollments', function (Blueprint $table): void {
            $table->boolean('sequential')->default(false);
        });
    }

    public function down(): void
    {
        if (DB::table('course_revisions')->where('sequential', true)->exists()
            || DB::table('course_enrollments')->where('sequential', true)->exists()
            || DB::table('course_module_progress')->whereRaw("validated_input->>'kind' = 'reading'")->exists()) {
            throw new RuntimeException('Preserve learning paths and reading history; use a compatible application release instead of dropping their policy.');
        }
        Schema::table('course_enrollments', fn (Blueprint $table) => $table->dropColumn('sequential'));
        Schema::table('course_revisions', fn (Blueprint $table) => $table->dropColumn('sequential'));
    }
};
