<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherAccessSeeder extends Seeder
{
    public function run(): void
    {
        // Role ID 2 is Teacher
        $teacherRoleId = 2;

        // 1. Assign Menus to Teacher
        // Dashboard (1), Academic Year (3), Subject (6), Class (12)
        // Note: Check MenuSeeder for IDs. Assumed based on analysis: 
        // 1=Dashboard, 3=Academic Year, 6=Subject, 12=Class
        $menuIds = [1, 3, 6, 12];

        foreach ($menuIds as $menuId) {
            DB::table('role_menus')->insertOrIgnore([
                'role_id' => $teacherRoleId,
                'menu_id' => $menuId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Assign Permissions
        // We assign these permissions primarily to the 'Class' menu (ID 12) as that is the main workspace for teachers.

        $permissionNames = [
            // Class Management
            'view_class',
            'view_class_members',

            // Assignment / Task Management
            'create_assignment',
            'edit_assignment',
            'delete_assignment',
            'view_assignment',
            'view_submissions',

            // Grading
            'grade_assignment',
            'view_grades',

            // Learning Materials
            'create_material',
            'view_material',
            'delete_material',

            // AI Features
            'run_ai_review',
            'ai_student_report'
        ];

        $permissions = DB::table('permissions')->whereIn('name', $permissionNames)->get();
        $targetMenuId = 12; // Class Menu

        foreach ($permissions as $perm) {
            DB::table('role_menu_permissions')->insertOrIgnore([
                'role_id' => $teacherRoleId,
                'menu_id' => $targetMenuId,
                'permission_id' => $perm->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Optional: If Teachers need to view Subjects or Academic Years effectively, 
        // we might want to add 'view_subject' or 'view_academic_year' permissions if they exist.
        // Based on PermissionSeeder: 'view_class' exists. 'view_schedule' exists.

        $this->command->info('Teacher Access Seeded: Menus and Permissions assigned.');
    }
}
