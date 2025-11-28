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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Generated name, e.g., "10 RPL 1"');
            $table->string('suffix')->nullable()->comment('Class suffix, e.g., "A", "1"');
            $table->tinyInteger('grade_level')->comment('e.g., 10, 11, 12');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('educational_level_id');
            $table->unsignedBigInteger('major_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
