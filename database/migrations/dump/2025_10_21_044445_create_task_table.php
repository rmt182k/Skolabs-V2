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
        Schema::create('task', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id'); // Tanpa constraint
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['task', 'quiz', 'exam'])->default('task');
            $table->unsignedInteger('total_possible_score')->default(0)->comment('Total skor maksimum dari semua pertanyaan.');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task');
    }
};
