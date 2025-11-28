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
        // User -> Menu Override (User diberi akses khusus ke menu tertentu, bypass role)
        Schema::create('user_menu_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('menu_id');
            $table->enum('access_type', ['grant', 'revoke'])->comment('grant = tambah akses, revoke = cabut akses');
            $table->timestamps();
            $table->unique(['user_id', 'menu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_menu_overrides');
    }
};
