<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_revision_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_revision_id')->constrained('course_revisions')->restrictOnDelete();
            $table->foreignId('module_id')->constrained('learning_modules')->restrictOnDelete();
            $table->unsignedBigInteger('module_revision_id');
            $table->integer('position');
            $table->unique(['course_revision_id', 'module_id']);
            $table->unique(['course_revision_id', 'position']);
        });
        DB::statement('ALTER TABLE course_revision_modules ADD CONSTRAINT course_revision_modules_revision_fk FOREIGN KEY (module_revision_id, module_id) REFERENCES module_revisions (id, module_id), ADD CONSTRAINT course_revision_modules_position_check CHECK (position > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('course_revision_modules');
    }
};
