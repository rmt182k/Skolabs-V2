<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MasterDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Academic Year
        $academicYearId = DB::table('academic_years')->insertGetId([
            'year' => '2025/2026',
            'semester' => 'odd',
            'name' => 'Ganjil 2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Educational Levels
        $levels = [
            ['name' => 'SD', 'duration_years' => 6, 'description' => 'Sekolah Dasar'],
            ['name' => 'SMP', 'duration_years' => 3, 'description' => 'Sekolah Menengah Pertama'],
            ['name' => 'SMA', 'duration_years' => 3, 'description' => 'Sekolah Menengah Atas'],
            ['name' => 'SMK', 'duration_years' => 3, 'description' => 'Sekolah Menengah Kejuruan'],
        ];

        foreach ($levels as $lvl) {
            DB::table('educational_levels')->insert($lvl + ['created_at' => now(), 'updated_at' => now()]);
        }

        // Ambil ID Level
        $sdId = DB::table('educational_levels')->where('name', 'SD')->value('id');
        $smaId = DB::table('educational_levels')->where('name', 'SMA')->value('id');
        $smkId = DB::table('educational_levels')->where('name', 'SMK')->value('id');

        // 3. Majors (Jurusan)
        $majors = [
            // SMA
            ['educational_level_id' => $smaId, 'code' => 'MIPA', 'name' => 'Matematika dan Ilmu Pengetahuan Alam', 'description' => 'Science'],
            ['educational_level_id' => $smaId, 'code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial', 'description' => 'Social'],
            ['educational_level_id' => $smaId, 'code' => 'BHS', 'name' => 'Bahasa dan Budaya', 'description' => 'Language'],
            // SMK (5 Jurusan)
            ['educational_level_id' => $smkId, 'code' => 'TKJ', 'name' => 'Teknik Komputer dan Jaringan', 'description' => 'IT Network'],
            ['educational_level_id' => $smkId, 'code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak', 'description' => 'Software Engineering'],
            ['educational_level_id' => $smkId, 'code' => 'DKV', 'name' => 'Desain Komunikasi Visual', 'description' => 'Design'],
            ['educational_level_id' => $smkId, 'code' => 'AKL', 'name' => 'Akuntansi dan Keuangan Lembaga', 'description' => 'Accounting'],
            ['educational_level_id' => $smkId, 'code' => 'OTKP', 'name' => 'Otomatisasi Tata Kelola Perkantoran', 'description' => 'Office Mgmt'],
        ];

        DB::table('majors')->insert(array_map(function ($m) {
            return $m + ['created_at' => now(), 'updated_at' => now()];
        }, $majors));

        // 4. Subjects (Mata Pelajaran Umum)
        $subjects = [
            ['name' => 'Matematika', 'code' => 'MTK'],
            ['name' => 'Bahasa Indonesia', 'code' => 'IND'],
            ['name' => 'Bahasa Inggris', 'code' => 'ING'],
            ['name' => 'Ilmu Pengetahuan Alam', 'code' => 'IPA'],
            ['name' => 'Pendidikan Agama', 'code' => 'PAI'],
            ['name' => 'Penjaskes', 'code' => 'PJK'],
            ['name' => 'Sejarah', 'code' => 'SEJ'],
            ['name' => 'Seni Budaya', 'code' => 'SBD'],
            ['name' => 'Fisika', 'code' => 'FIS'], // SMA/SMK
            ['name' => 'Kimia', 'code' => 'KIM'], // SMA/SMK
            ['name' => 'Biologi', 'code' => 'BIO'], // SMA/SMK
            ['name' => 'Produktif Kejuruan', 'code' => 'PROD'], // SMK
        ];

        DB::table('subjects')->insert(array_map(function ($s) {
            return $s + ['created_at' => now(), 'updated_at' => now()];
        }, $subjects));
    }
}
