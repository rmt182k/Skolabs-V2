<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear the table for reseeding
        DB::table('roles')->delete();

        DB::table('roles')->insert([
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Has full access to all system features.',
                'badge_color' => 'danger',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'teacher',
                'display_name' => 'Teacher',
                'description' => 'Role for teacher/instructor users.',
                'badge_color' => 'success',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'student',
                'display_name' => 'Student',
                'description' => 'Role for student/pupil users.',
                'badge_color' => 'primary',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

