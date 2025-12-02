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
        // Gunakan '*' agar variabel masuk ke semua Layout DAN Child View
        View::composer('*', function ($view) {

            static $globalData = null;

            if ($globalData === null) {
                $user = Auth::user();

                // Default values
                $roles = collect([]); // Default collection kosong
                $permissions = [];
                $studentClasses = [];
                $teacherClasses = [];

                if ($user) {
                    // --- 1. ROLE & PERMISSION LOGIC ---

                    // [BARU] Ambil Data Role Lengkap (Join user_roles -> roles)
                    $roles = DB::table('user_roles')
                        ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                        ->where('user_roles.user_id', $user->id)
                        ->select(
                            'roles.id',
                            'roles.name',           // ex: admin, teacher
                            'roles.display_name',   // ex: Administrator, Guru
                            'roles.badge_color',    // ex: primary, danger
                            'roles.description'
                        )
                        ->get();

                    // Ambil ID dari hasil query roles di atas untuk dipakai query permission
                    // (Menghemat satu query ke database)
                    $roleIds = $roles->pluck('id')->toArray();

                    // Logic Permission (Sama seperti sebelumnya, tapi pakai $roleIds dari variabel di atas)
                    $rolePerms = DB::table('role_menu_permissions')
                        ->join('permissions', 'role_menu_permissions.permission_id', '=', 'permissions.id')
                        ->whereIn('role_menu_permissions.role_id', $roleIds)
                        ->distinct()
                        ->pluck('permissions.name')
                        ->toArray();

                    $overrides = DB::table('user_menu_permission_overrides')
                        ->join('permissions', 'user_menu_permission_overrides.permission_id', '=', 'permissions.id')
                        ->where('user_menu_permission_overrides.user_id', $user->id)
                        ->select('permissions.name', 'user_menu_permission_overrides.access_type')
                        ->get();

                    $permissions = array_unique($rolePerms);

                    foreach ($overrides as $override) {
                        if ($override->access_type === 'grant') {
                            if (!in_array($override->name, $permissions)) {
                                $permissions[] = $override->name;
                            }
                        } elseif ($override->access_type === 'revoke') {
                            $permissions = array_diff($permissions, [$override->name]);
                        }
                    }

                    // --- 2. ACADEMIC LOGIC (Sama seperti sebelumnya) ---
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
                    'roles' => $roles, // Data Role masuk sini
                    'permissions' => array_values($permissions),
                    'studentClasses' => $studentClasses,
                    'teacherClasses' => $teacherClasses
                ];
            }

            // --- 3. INJECT KE VIEW ---
            $view->with('globalAuthUser', $globalData['user']);
            $view->with('globalRoles', $globalData['roles']); // Inject variabel baru
            $view->with('globalPermissions', $globalData['permissions']);
            $view->with('globalStudentClasses', $globalData['studentClasses']);
            $view->with('globalTeacherClasses', $globalData['teacherClasses']);
        });
    }
}
