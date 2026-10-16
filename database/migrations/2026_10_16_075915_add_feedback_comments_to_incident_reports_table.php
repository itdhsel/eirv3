<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->text('feedback_comments')->nullable()->after('review_comments');
        });
    }

    public function down(): void
    {
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->dropColumn('feedback_comments');
        });
    }
};