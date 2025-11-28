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
        Schema::create('task_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('student_id');
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('final_grade', 5, 2)->nullable()->comment('Nilai akhir numerik total.');
            $table->text('teacher_feedback')->nullable();
            $table->enum('status', ['pending', 'submitted', 'late', 'graded'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
