<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operations_heartbeats', function (Blueprint $table): void {
            $table->uuid('probe_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('operations_heartbeats', function (Blueprint $table): void {
            $table->dropColumn('probe_id');
        });
    }
};
