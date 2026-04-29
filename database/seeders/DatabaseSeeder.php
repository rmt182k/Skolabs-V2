<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. RBAC (Punya Kamu)
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);

        // 2. Menu (Punya Kamu)
        $this->call(MenuSeeder::class);
        $this->call(AdminAccessSeeder::class);
        $this->call(TeacherAccessSeeder::class);

        // 3. Master Data (Baru)
        $this->call(MasterDataSeeder::class);

        // 4. Users (Admin & Guru) (Baru)
        $this->call(UserManagementSeeder::class);

        // 5. Classes, Students, Schedules (Baru)
        $this->call(AcademicSeeder::class);

        // 6. Task Demo (Baru)
        // $this->call(TaskDemoSeeder::class);

        // 7. Student Access (Baru)
        $this->call(StudentAccessSeeder::class);
    }
}
