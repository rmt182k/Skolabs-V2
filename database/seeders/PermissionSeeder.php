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

        $permissions = [
            // --- Basic CRUD Permissions ---
            [
                'name' => 'view',
                'display_name' => 'View',
                'description' => 'Dapat melihat data.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'create',
                'display_name' => 'Create',
                'description' => 'Dapat membuat data baru.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'edit',
                'display_name' => 'Edit',
                'description' => 'Dapat mengedit data yang ada.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'delete',
                'display_name' => 'Delete',
                'description' => 'Dapat menghapus data.',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // --- Approval & Management Permissions ---
            [
                'name' => 'approve',
                'display_name' => 'Approve',
                'description' => 'Dapat menyetujui data atau permintaan.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'reject',
                'display_name' => 'Reject',
                'description' => 'Dapat menolak data atau permintaan.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'manage',
                'display_name' => 'Manage',
                'description' => 'Dapat mengatur konfigurasi sistem.',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // --- Data Handling Permissions ---
            [
                'name' => 'export',
                'display_name' => 'Export',
                'description' => 'Dapat mengekspor data ke file.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'import',
                'display_name' => 'Import',
                'description' => 'Dapat mengimpor data dari file.',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // --- LMS Specific Permissions (REQUESTED) ---
            [
                'name' => 'answer',
                'display_name' => 'Answer',
                'description' => 'Dapat mengerjakan dan mengirim jawaban tugas/kuis.', // Khusus untuk Student
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'grade',
                'display_name' => 'Grade',
                'description' => 'Dapat memberikan nilai pada jawaban siswa.', // Khusus untuk Teacher
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'generate_ai',
                'display_name' => 'Generate AI',
                'description' => 'Dapat menggunakan fitur AI untuk evaluasi atau pembuatan soal.', // Sesuai fitur AI LMS kamu
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Menggunakan insertOrIgnore agar jika seeder dijalankan ulang tidak error karena duplikat
        DB::table('permissions')->insertOrIgnore($permissions);
    }
}
