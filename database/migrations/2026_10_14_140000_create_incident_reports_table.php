<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('submitted')->index();
            $table->date('reported_at');
            // Section A — Ministry of Health IR 2.0/2017
            $table->string('facility_name');
            $table->text('patient_name'); // encrypted Eloquent cast
            $table->date('incident_date')->nullable();
            $table->date('approximate_incident_date')->nullable();
            $table->time('incident_time')->nullable();
            $table->time('approximate_incident_time')->nullable();
            $table->text('patient_identifier')->nullable(); // encrypted Eloquent cast
            $table->unsignedTinyInteger('patient_age')->nullable();
            $table->string('ethnic')->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('patient_status', 16)->nullable();
            $table->string('language_barrier', 8)->nullable();
            $table->text('diagnosis')->nullable(); // encrypted Eloquent cast
            $table->string('patient_type', 20);
            $table->string('patient_type_other')->nullable();
            $table->json('departments');
            $table->string('departments_other')->nullable();
            $table->string('location_ward_clinic')->nullable();
            $table->string('incident_type', 12);
            $table->string('other_incident_type')->nullable();
            $table->text('description'); // encrypted Eloquent cast
            $table->string('patient_outcome', 24)->nullable();
            $table->text('immediate_action')->nullable(); // encrypted Eloquent cast
            $table->string('reporter_designation', 24);
            $table->string('reporter_designation_other')->nullable();
            $table->string('reporter_name');
            $table->boolean('reporter_attested');
            // Section B — completed by a QATU reviewer
            $table->json('actions_taken')->nullable();
            $table->text('review_comments')->nullable(); // encrypted Eloquent cast
            $table->boolean('eir_submitted')->nullable();
            $table->date('eir_submission_date')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_designation')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};
