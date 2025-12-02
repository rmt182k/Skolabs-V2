<?php

namespace App\Providers;

use Auth;
use DB;
use Illuminate\Support\ServiceProvider;
use View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {

            static $globalData = null;

            if ($globalData === null) {
                $user = Auth::user();

                // Default values
                $roles = collect([]);
                $permissions = []; // Array Flat (['perm-a', 'perm-b']) untuk logic check
                $permissionsByMenu = []; // Array Group (['Menu A' => ['perm-a']]) untuk display
                $studentClasses = [];
                $teacherClasses = [];

                if ($user) {
                    // --- 1. ROLE LOGIC ---
                    $roles = DB::table('user_roles')
                        ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                        ->where('user_roles.user_id', $user->id)
                        ->select('roles.id', 'roles.name', 'roles.display_name', 'roles.badge_color', 'roles.description')
                        ->get();

                    $roleIds = $roles->pluck('id')->toArray();

                    // --- 2. PERMISSION LOGIC (UPDATED WITH MENU) ---

                    // Kita ambil data Permission SEKALIGUS nama Menunya
                    $rawRolePerms = DB::table('role_menu_permissions')
                        ->join('permissions', 'role_menu_permissions.permission_id', '=', 'permissions.id')
                        ->join('menus', 'role_menu_permissions.menu_id', '=', 'menus.id')
                        ->whereIn('role_menu_permissions.role_id', $roleIds)
                        ->select(
                            'permissions.name as permission_name',
                            'menus.title as menu_name'
                        )
                        ->distinct()
                        ->get();

                    // A. Buat Flat Array (Untuk Logic Coding: in_array, dll)
                    $rolePermsFlat = $rawRolePerms->pluck('permission_name')->toArray();
                    $permissions = array_unique($rolePermsFlat);

                    // B. Buat Grouped Array (Untuk Tampilan: Menu A punya permission apa aja)
                    $permissionsByMenu = $rawRolePerms->groupBy('menu_name')->map(function ($item) {
                        return $item->pluck('permission_name')->toArray();
                    })->toArray();

                    // --- OVERRIDES LOGIC ---
                    $overrides = DB::table('user_menu_permission_overrides')
                        ->join('permissions', 'user_menu_permission_overrides.permission_id', '=', 'permissions.id')
                        ->where('user_menu_permission_overrides.user_id', $user->id)
                        ->select('permissions.name', 'user_menu_permission_overrides.access_type')
                        ->get();

                    foreach ($overrides as $override) {
                        if ($override->access_type === 'grant') {
                            if (!in_array($override->name, $permissions)) {
                                $permissions[] = $override->name;
                                // Note: Override Grant biasanya tidak punya info menu ID di tabel override,
                                // jadi masuk ke kategori 'Custom/Override' atau biarkan di flat array saja.
                                $permissionsByMenu['Extra Privileges'][] = $override->name;
                            }
                        } elseif ($override->access_type === 'revoke') {
                            $permissions = array_diff($permissions, [$override->name]);
                            // Kita tidak menghapus dari $permissionsByMenu agar tetap terlihat strukturnya,
                            // tapi secara logic sistem ($permissions flat), aksesnya sudah hilang.
                        }
                    }

                    // --- 3. ACADEMIC LOGIC ---
                    $activeYearId = DB::table('academic_years')->where('is_active', true)->value('id');

                    if ($activeYearId) {
                        // Student Logic
                        $studentClasses = DB::table('class_enrollments')
                            ->join('classes', 'class_enrollments.class_id', '=', 'classes.id')
                            ->where('class_enrollments.student_id', $user->id)
                            ->where('class_enrollments.academic_year_id', $activeYearId)
                            ->select('classes.id', 'classes.name', 'classes.grade_level', 'classes.suffix')
                            ->orderBy('classes.name')
                            ->get();

                        // Teacher Logic
                        $teacherClasses = DB::table('class_schedules')
                            ->join('classes', 'class_schedules.class_id', '=', 'classes.id')
                            ->where('class_schedules.user_id', $user->id)
                            ->where('classes.academic_year_id', $activeYearId)
                            ->select('classes.id', 'classes.name', 'classes.grade_level', 'classes.suffix')
                            ->distinct()
                            ->orderBy('classes.name')
                            ->get();
                    }
                }

                // Simpan ke static variable
                $globalData = [
                    'user' => $user,
                    'roles' => $roles,
                    'permissions' => array_values($permissions), // Flat Array (Existing)
                    'permissionsByMenu' => $permissionsByMenu,   // Grouped Array (NEW)
                    'studentClasses' => $studentClasses,
                    'teacherClasses' => $teacherClasses
                ];
            }

            // --- 4. INJECT KE VIEW ---
            $view->with('globalAuthUser', $globalData['user']);
            $view->with('globalRoles', $globalData['roles']);

            // Ini yang lama (flat array), jangan dihapus biar logic @can tidak error
            $view->with('globalPermissions', $globalData['permissions']);

            // [BARU] Ini yang ada info menunya
            $view->with('globalPermissionsByMenu', $globalData['permissionsByMenu']);

            $view->with('globalStudentClasses', $globalData['studentClasses']);
            $view->with('globalTeacherClasses', $globalData['teacherClasses']);
        });
    }
}
