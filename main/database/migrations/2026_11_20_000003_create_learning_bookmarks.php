<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_bookmarks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10);
            $table->unsignedBigInteger('content_id');
            $table->timestampTz('created_at');
            $table->unique(['user_id', 'kind', 'content_id']);
        });
        DB::statement("ALTER TABLE learning_bookmarks ADD CONSTRAINT learning_bookmark_kind_check CHECK (kind IN ('module', 'course'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_bookmarks');
    }
};
