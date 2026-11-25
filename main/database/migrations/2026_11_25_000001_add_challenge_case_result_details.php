<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenge_case_evaluations', fn (Blueprint $table) => $table->text('result_details')->nullable());
    }

    public function down(): void
    {
        Schema::table('challenge_case_evaluations', fn (Blueprint $table) => $table->dropColumn('result_details'));
    }
};
