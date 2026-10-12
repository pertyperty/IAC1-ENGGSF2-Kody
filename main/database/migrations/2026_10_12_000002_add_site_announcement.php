<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_announcements', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('title', 80)->default('');
            $table->string('message', 500)->default('');
            $table->string('destination', 20)->default('none');
            $table->boolean('active')->default(false);
            $table->unsignedInteger('record_version')->default(1);
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('updated_at')->nullable();
        });
        DB::statement("ALTER TABLE site_announcements ADD CONSTRAINT site_announcement_rules CHECK (id = 1 AND record_version > 0 AND destination IN ('none', 'help', 'contact', 'learn') AND (NOT active OR (length(trim(title)) > 0 AND length(trim(message)) > 0)))");
        DB::table('site_announcements')->insert(['id' => 1]);
    }

    public function down(): void
    {
        if (DB::table('site_announcements')->where('record_version', '>', 1)->exists()) {
            throw new RuntimeException('Preserve managed announcement state; roll back code without removing this table.');
        }
        Schema::dropIfExists('site_announcements');
    }
};
