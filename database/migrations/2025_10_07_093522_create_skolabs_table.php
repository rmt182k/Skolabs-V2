<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ==========================================
        // USER TABLE
        // ==========================================

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->boolean('is_active')->default('1');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // ==========================================
        // USER DETAIL TABLE
        // ==========================================

        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('identity_number')->unique()->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // RBAC TABLES
        // ==========================================

        // Tabel Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('badge_color', 20)->default('primary');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // User -> Role (Many to Many)
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
        });

        // Tabel Permissions - Ini adalah daftar aksi yang bisa dilakukan
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // ACADEMIC TABLES
        // ==========================================

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('year', 9);
            $table->enum('semester', ['odd', 'even']);
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->unique(['year', 'semester']);
        });

        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->timestamps();
            $table->unique(['class_id', 'student_id', 'academic_year_id'], 'enrollment_unique');
        });

        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id'); // ID Kelas
            $table->unsignedBigInteger('subject_id'); // ID Mata Pelajaran
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type', 50)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('link_url')->nullable();
            $table->timestamps();
        });

        Schema::create('educational_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('duration_years');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('subjects_assignment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['subject_id', 'user_id']);
        });

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

        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('educational_level_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            // $table->unsignedBigInteger('academic_year_id');
            $table->string('day_name', 20);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('class_enrollments');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('user_details');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('educational_levels');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('majors');
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('subjects_assignment');
    }
};
