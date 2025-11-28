<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(class: PermissionSeeder::class);
        $this->call(class: EducationalLevelSeeder::class);
        $this->call(class: MajorSeeder::class);
        $this->call(class: SubjectSeeder::class);
        $this->call(class: RoleSeeder::class);
        $this->call(class: MenuSeeder::class);
        $this->call(class: RoleMenuSeeder::class);
        $this->call(class: UserSeeder::class);
        $this->call(class: TeacherSeeder::class);
        $this->call(class: StudentSeeder::class);
    }
}
