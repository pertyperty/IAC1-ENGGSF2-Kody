<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $windows = ['users' => 'created_at', 'learning_modules' => 'created_at', 'learning_courses' => 'created_at',
        'coding_challenges' => 'created_at', 'learning_activity_days' => 'completed_at', 'course_enrollments' => 'enrolled_at',
        'course_module_progress' => 'completed_at', 'challenge_submissions' => 'submitted_at'];

    public function up(): void
    {
        foreach ($this->windows as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($column, $table.'_report_window'));
        }
    }

    public function down(): void
    {
        foreach ($this->windows as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($table.'_report_window'));
        }
    }
};
