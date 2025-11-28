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
        Schema::create('task_submission_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_submission_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('question_option_id')->nullable();
            $table->text('answer_text')->nullable();
            $table->decimal('score_awarded', 5, 2)->nullable()->comment('Skor total yang didapat untuk jawaban ini.');
            $table->text('teacher_comment')->nullable()->comment('Feedback guru khusus untuk jawaban ini.');
            $table->timestamps();

            $table->unique(['task_submission_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_submission_answers');
    }
};
