<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('menus')->truncate();

        DB::table('menus')->insert([
            // ID: 1 (Dashboard)
            [
                'title' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'parent_id' => null,
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 2 (Sebelumnya ID 4)
            [
                'title' => 'Master Data',
                'url' => '#',
                'icon' => 'fas fa-database',
                'parent_id' => null,
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 3 (Parent ID merujuk ke ID 2 yaitu Master Data)
            [
                'title' => 'Academic Year',
                'url' => '/academic-years',
                'icon' => 'fas fa-circle',
                'parent_id' => 2,
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 4
            [
                'title' => 'Major',
                'url' => '/majors',
                'icon' => 'fas fa-circle',
                'parent_id' => 2,
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 5
            [
                'title' => 'Educational Level',
                'url' => '/educational-levels',
                'icon' => 'fas fa-circle',
                'parent_id' => 2,
                'order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 6
            [
                'title' => 'Subject',
                'url' => '/subjects',
                'icon' => 'fas fa-circle',
                'parent_id' => 2,
                'order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 7 (Sebelumnya ID 9)
            [
                'title' => 'Users & Menu',
                'url' => '#',
                'icon' => 'fas fa-user-shield',
                'parent_id' => null,
                'order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 8 (Parent ID merujuk ke ID 7 yaitu Users & Menu)
            [
                'title' => 'Users',
                'url' => '/users',
                'icon' => 'fas fa-circle',
                'parent_id' => 7,
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 9
            [
                'title' => 'Role',
                'url' => '/roles',
                'icon' => 'fas fa-circle',
                'parent_id' => 7,
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 10
            [
                'title' => 'Permission',
                'url' => '/permissions',
                'icon' => 'fas fa-circle',
                'parent_id' => 7,
                'order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 11
            [
                'title' => 'Menu',
                'url' => '/menus',
                'icon' => 'fas fa-circle',
                'parent_id' => 7,
                'order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID: 12 (Parent ID 2 - Master Data)
            [
                'title' => 'Class',
                'url' => '/classes',
                'icon' => 'fas fa-circle',
                'parent_id' => 2,
                'order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
