<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PermissionSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk tabel permissions.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // ==========================================
        // 1. MANAJEMEN PENGGUNA (User Management)
        // ==========================================
        // Disederhanakan menjadi umum (create_user, edit_user, dll)
        // Tidak lagi dipisah per role (admin/teacher/student)
        $userPermissions = [
            ['name' => 'create_user', 'display_name' => 'Create User', 'description' => 'Dapat membuat data user baru (Admin, Teacher, atau Student).'],
            ['name' => 'edit_user',   'display_name' => 'Edit User',   'description' => 'Dapat mengedit data user apapun.'],
            ['name' => 'delete_user', 'display_name' => 'Delete User', 'description' => 'Dapat menghapus data user.'],
            ['name' => 'view_user',   'display_name' => 'View User',   'description' => 'Dapat melihat daftar seluruh user.'],
        ];

        // ==========================================
        // 2. MANAJEMEN KELAS & JADWAL (Academic)
        // ==========================================
        $academicPermissions = [
            // --- Manajemen Kelas (Master Data) ---
            ['name' => 'create_class', 'display_name' => 'Create Class', 'description' => 'Dapat membuat kelas mata pelajaran baru.'],
            ['name' => 'edit_class',   'display_name' => 'Edit Class',   'description' => 'Dapat mengedit informasi kelas.'],
            ['name' => 'delete_class', 'display_name' => 'Delete Class', 'description' => 'Dapat menghapus kelas.'],
            ['name' => 'view_class',   'display_name' => 'View Class',   'description' => 'Dapat melihat daftar kelas yang tersedia.'],

            // --- Pendaftaran Siswa (Enrollment) ---
            // Permission ini tetap spesifik karena konteksnya adalah "mendaftarkan ke dalam kelas"
            ['name' => 'enroll_student',     'display_name' => 'Enroll Student',   'description' => 'Dapat mendaftarkan/memasukkan siswa ke dalam kelas.'],
            ['name' => 'kick_student',       'display_name' => 'Kick Student',     'description' => 'Dapat mengeluarkan siswa dari kelas.'],
            ['name' => 'view_class_members', 'display_name' => 'View Members',     'description' => 'Dapat melihat siapa saja siswa di dalam kelas.'],

            // --- Manajemen Jadwal ---
            ['name' => 'create_schedule', 'display_name' => 'Create Schedule', 'description' => 'Dapat membuat jadwal pelajaran baru.'],
            ['name' => 'edit_schedule',   'display_name' => 'Edit Schedule',   'description' => 'Dapat mengubah jadwal pelajaran.'],
            ['name' => 'delete_schedule', 'display_name' => 'Delete Schedule', 'description' => 'Dapat menghapus jadwal pelajaran.'],
            ['name' => 'view_schedule',   'display_name' => 'View Schedule',   'description' => 'Dapat melihat jadwal pelajaran.'],
        ];

        // ==========================================
        // 3. MATERI & TUGAS (LMS Learning)
        // ==========================================
        $lmsPermissions = [
            // --- Materi Pelajaran ---
            ['name' => 'upload_material', 'display_name' => 'Upload Material', 'description' => 'Dapat mengunggah materi pelajaran (PDF/PPT).'],
            ['name' => 'delete_material', 'display_name' => 'Delete Material', 'description' => 'Dapat menghapus materi pelajaran.'],
            ['name' => 'view_material',   'display_name' => 'View Material',   'description' => 'Dapat mengunduh/melihat materi pelajaran.'],

            // --- Tugas (Assignment) ---
            ['name' => 'create_assignment', 'display_name' => 'Create Assignment', 'description' => 'Dapat membuat tugas baru.'],
            ['name' => 'edit_assignment',   'display_name' => 'Edit Assignment',   'description' => 'Dapat mengedit instruksi tugas.'],
            ['name' => 'delete_assignment', 'display_name' => 'Delete Assignment', 'description' => 'Dapat menghapus tugas.'],
            ['name' => 'view_assignment',   'display_name' => 'View Assignment',   'description' => 'Dapat melihat detail tugas.'],

            // --- Mengerjakan Tugas (Student Context) ---
            ['name' => 'submit_assignment', 'display_name' => 'Submit Assignment', 'description' => 'Dapat mengirimkan jawaban tugas.'],

            // --- Penilaian (Grading) ---
            ['name' => 'grade_assignment', 'display_name' => 'Grade Assignment', 'description' => 'Dapat memberi nilai pada tugas siswa.'],
            ['name' => 'view_grades',      'display_name' => 'View Grades',      'description' => 'Dapat melihat rekap nilai.'],
            ['name' => 'export_grades',    'display_name' => 'Export Grades',    'description' => 'Dapat mendownload nilai ke Excel/PDF.'],
        ];

        // ==========================================
        // 4. FITUR AI & SISTEM
        // ==========================================
        $systemPermissions = [
            // --- AI Features ---
            ['name' => 'generate_ai_quiz',  'display_name' => 'Generate AI Quiz', 'description' => 'Dapat membuat soal otomatis menggunakan AI.'],
            ['name' => 'ai_student_report', 'display_name' => 'AI Student Report',     'description' => 'Dapat merangkum Report Siswa menggunakan AI.'],

            // --- System Settings ---
            ['name' => 'manage_settings',  'display_name' => 'Manage Settings',  'description' => 'Dapat mengubah konfigurasi aplikasi.'],
        ];

        // Gabungkan semua array
        $allPermissions = array_merge($userPermissions, $academicPermissions, $lmsPermissions, $systemPermissions);

        // Tambahkan Timestamp
        $finalData = array_map(function ($perm) use ($now) {
            return array_merge($perm, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $allPermissions);

        // Insert ke database (gunakan insertOrIgnore agar aman jika dijalankan ulang)
        DB::table('permissions')->insertOrIgnore($finalData);
    }
}
