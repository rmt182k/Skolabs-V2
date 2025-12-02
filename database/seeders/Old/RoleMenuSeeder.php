<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('role_menus')->truncate();

        DB::table('role_menus')->insert([
            // --- AKSES DASHBOARD UNTUK SEMUA ROLE ---
            [
                'role_id' => 1, // Admin
                'menu_id' => 1, // Dashboard Unified
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 2, // Teacher
                'menu_id' => 1, // Dashboard Unified
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 3, // Student
                'menu_id' => 1, // Dashboard Unified
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- AKSES MENU LAIN UNTUK ADMIN (Role 1) ---
            [
                'role_id' => 1,
                'menu_id' => 2, // Master Data
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'menu_id' => 3, // Academic Year
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'menu_id' => 4, // Major
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'menu_id' => 5, // Educational Level
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'menu_id' => 6, // Subject
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'menu_id' => 7, // Users & Menu
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'menu_id' => 8, // Users
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'menu_id' => 9, // Role
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'menu_id' => 10, // Permission
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'menu_id' => 11, // Menu
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'menu_id' => 12, // Class
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
