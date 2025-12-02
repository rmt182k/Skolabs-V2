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
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            $permissions = [];
            $studentClasses = []; // Default kosong
            $teacherClasses = []; // Default kosong

            if ($user) {
                // ====================================================
                // 1. PERMISSION LOGIC (EXISTING)
                // ====================================================
                $roleIds = DB::table('user_roles')->where('user_id', $user->id)->pluck('role_id');

                $rolePerms = DB::table('role_menu_permissions')
                    ->join('permissions', 'role_menu_permissions.permission_id', '=', 'permissions.id')
                    ->whereIn('role_menu_permissions.role_id', $roleIds)
                    ->pluck('permissions.name')
                    ->toArray();

                $overrides = DB::table('user_menu_permission_overrides')
                    ->join('permissions', 'user_menu_permission_overrides.permission_id', '=', 'permissions.id')
                    ->where('user_id', $user->id)
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

                // ====================================================
                // 2. ACADEMIC & CLASS LOGIC (NEW)
                // ====================================================

                // Ambil Tahun Ajaran Aktif (PENTING: Agar tidak muncul kelas tahun lalu)
                $activeYearId = DB::table('academic_years')->where('is_active', true)->value('id');

                if ($activeYearId) {
                    // A. JIKA STUDENT: Ambil dari class_enrollments
                    // Logic: Cek apakah user ID ini terdaftar di enrollment pada tahun ajar aktif
                    $studentClasses = DB::table('class_enrollments')
                        ->join('classes', 'class_enrollments.class_id', '=', 'classes.id')
                        ->where('class_enrollments.student_id', $user->id)
                        ->where('class_enrollments.academic_year_id', $activeYearId)
                        ->select('classes.id', 'classes.name', 'classes.grade_level')
                        ->orderBy('classes.name')
                        ->get();

                    // B. JIKA TEACHER: Ambil dari class_schedules
                    // Logic: Cek apakah user ID ini punya jadwal mengajar di kelas yang tahun ajarnya aktif
                    $teacherClasses = DB::table('class_schedules')
                        ->join('classes', 'class_schedules.class_id', '=', 'classes.id')
                        ->where('class_schedules.user_id', $user->id)
                        ->where('classes.academic_year_id', $activeYearId) // Filter via relasi classes ke academic_years
                        ->select('classes.id', 'classes.name', 'classes.grade_level')
                        ->distinct() // PENTING: Agar kelas tidak duplikat jika guru mengajar mapel berbeda di kelas sama
                        ->orderBy('classes.name')
                        ->get();
                }
            }

            // ====================================================
            // 3. INJECT KE VIEW
            // ====================================================
            $view->with('globalAuthUser', $user);
            $view->with('globalPermissions', array_values($permissions));

            // Variable Baru: Bisa dipakai di Navbar/Sidebar/JS
            $view->with('globalStudentClasses', $studentClasses);
            $view->with('globalTeacherClasses', $teacherClasses);
        });
    }
}
