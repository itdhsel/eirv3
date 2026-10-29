<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incident_rcas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_report_id')->constrained()->cascadeOnDelete();
            
            // Section 4: Investigation Team
            $table->json('investigation_team')->nullable(); // { "leader": "", "members": [] }
            
            // Section 5: Summary
            $table->text('summary')->nullable();
            
            // Section 6: Sequence of Events (Dynamic Table)
            $table->json('sequence_of_events')->nullable(); // Array of { date, time, location, description, person, comments }
            
            // Sections 7 & 8: Fishbone / Contributing Factors
            $table->json('contributing_factors')->nullable(); // Categorized by Team, Individual, Patient, Task, Mgt, Env, External
            
            // Section 9: 5 Rules of Causation Table
            $table->json('causation_analysis')->nullable(); // Array of { root_cause, causing, effect, leading_to, event }
            
            // Section 10: Root Causes summary
            $table->text('root_causes')->nullable();
            
            // Section 11: Action Plan Table
            $table->json('action_plan')->nullable(); // Array of { factor, description, hierarchy, person, evidence, date }
            
            // Workflow & Approvals
            $table->string('status')->default('draft'); // draft, pending_hod, pending_qa, approved, rejected
            
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            
            $table->foreignId('verified_by_hod')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            
            $table->foreignId('approved_by_qa')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_rcas');
    }
};
