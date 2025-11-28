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
        // Tabel Menus
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url')->default('#');
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('role_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('menu_id');
            $table->timestamps();
            $table->unique(['role_id', 'menu_id']);
        });

        Schema::create('role_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'menu_id', 'permission_id'], 'role_menu_perm_unique');
        });

        Schema::create('user_menu_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('menu_id');
            $table->enum('access_type', ['grant', 'revoke'])->comment('grant = tambah akses, revoke = cabut akses');
            $table->timestamps();
            $table->unique(['user_id', 'menu_id']);
        });

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
        Schema::dropIfExists('menus');
        Schema::dropIfExists('role_menus');
        Schema::dropIfExists('role_menu_permissions');
        Schema::dropIfExists('user_menu_overrides');
        Schema::dropIfExists('user_menu_permission_overrides');
    }
};
