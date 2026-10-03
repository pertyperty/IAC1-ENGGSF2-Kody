<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->foreignId('notifiable_id')->constrained('users')->cascadeOnDelete();
            $table->jsonb('data');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
            $table->index(['notifiable_id', 'created_at']);
        });
        DB::statement("CREATE UNIQUE INDEX notifications_module_review_unique ON notifications (notifiable_id, (data->>'revision_id')) WHERE type = 'module.reviewed'");
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
