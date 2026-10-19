<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nokp')->nullable()->unique()->after('id');
            $table->string('jabatan')->nullable()->after('nokp');
            
            // If your external DB doesn't have emails, you must make the local email column nullable
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nokp', 'jabatan']);
            $table->string('email')->nullable(false)->change();
        });
    }
};