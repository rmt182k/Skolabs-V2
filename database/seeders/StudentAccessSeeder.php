<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentAccessSeeder extends Seeder
{
    public function run(): void
    {
        // Role ID 3 is Student (based on RoleSeeder logic usually: 1=Admin, 2=Teacher, 3=Student)
        // Let's verify or fetch by name just in case
        $studentRoleId = DB::table('roles')->where('name', 'student')->value('id');

        if (!$studentRoleId) {
            $studentRoleId = 3; // Fallback
        }

        // 1. Assign Menus to Student
        // Dashboard (1), Class (12)
        // IDs are based on MenuSeeder
        $menuIds = [1, 12];

        foreach ($menuIds as $menuId) {
            DB::table('role_menus')->insertOrIgnore([
                'role_id' => $studentRoleId,
                'menu_id' => $menuId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Assign Permissions
        // We assign these permissions primarily to the 'Class' menu (ID 12) context.

        $permissionNames = [
            // Class Access
            'view_class',
            'view_class_members',

            // Assignment / Task Interaction
            'view_assignment',
            'submit_assignment',
            'view_submissions', // To see their own submissions list

            // Grading / Results
            'view_grades',

            // Learning Materials
            'view_material',
        ];

        $permissions = DB::table('permissions')->whereIn('name', $permissionNames)->get();
        $targetMenuId = 12; // Class Menu

        foreach ($permissions as $perm) {
            DB::table('role_menu_permissions')->insertOrIgnore([
                'role_id' => $studentRoleId,
                'menu_id' => $targetMenuId,
                'permission_id' => $perm->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Student Access Seeded: Menus and Permissions assigned.');
    }
}
