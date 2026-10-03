<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_application_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instructor_application_id')->constrained()->restrictOnDelete();
            $table->integer('application_version');
            $table->string('institution_name', 100);
            $table->string('specialization', 100);
            $table->string('credential_disk');
            $table->string('credential_path');
            $table->string('verification_status', 10)->default('Pending');
            $table->string('verification_notes', 255)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('created_at');
            $table->unique(['instructor_application_id', 'application_version']);
        });
        DB::statement("ALTER TABLE instructor_application_versions ADD CONSTRAINT instructor_version_values CHECK (application_version > 0 AND verification_status IN ('Pending','Approved','Rejected'))");
        // Capture only existing known state; earlier submissions cannot be reconstructed.
        DB::statement('INSERT INTO instructor_application_versions (instructor_application_id, application_version, institution_name, specialization, credential_disk, credential_path, verification_status, verification_notes, reviewed_by, verified_at, created_at) SELECT id, record_version, institution_name, specialization, credential_disk, credential_path, verification_status, verification_notes, reviewed_by, verified_at, created_at FROM instructor_applications');
    }

    public function down(): void
    {
        if (DB::table('instructor_application_versions')->exists()) {
            throw new RuntimeException('Instructor credential history must be preserved. Roll back application code without reverting this migration.');
        }
        Schema::dropIfExists('instructor_application_versions');
    }
};
