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
        // User -> Menu -> Permission Override (Di menu tertentu, user punya permission khusus)
        Schema::create('user_menu_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('permission_id');
            $table->enum('access_type', ['grant', 'revoke']);
            $table->timestamps();
            $table->unique(['user_id', 'menu_id', 'permission_id'], 'user_menu_perm_override_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_menu_permission_overrides');
    }
};
