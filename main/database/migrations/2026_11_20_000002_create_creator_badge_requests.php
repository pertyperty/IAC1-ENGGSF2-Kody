<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_badge_requests', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestampTz('requested_at');
            $table->timestampTz('resolved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_badge_requests');
    }
};
