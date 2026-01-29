<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->timestamp('grading_started_at')->nullable()->after('submitted_at');
            $table->integer('grading_duration_seconds')->nullable()->after('grading_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->dropColumn(['grading_started_at', 'grading_duration_seconds']);
        });
    }
};
