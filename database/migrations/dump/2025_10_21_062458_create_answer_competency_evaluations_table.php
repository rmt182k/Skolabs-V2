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
        Schema::create('answer_competency_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_submission_answer_id'); // FK ke submission_answers
            $table->unsignedBigInteger('competency_id'); // FK ke competencies
            $table->decimal('score_awarded', 5, 2)->comment('Skor yang diberikan untuk kompetensi spesifik ini pada jawaban tersebut (Analisis Detail).');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answer_competency_evaluations');
    }
};
