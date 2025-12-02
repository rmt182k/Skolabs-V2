<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('classes')->truncate();
        $classes = [];

        // 1. Ambil ID Jenjang Pendidikan
        // PERBAIKAN: Menggunakan keyBy('name') karena EducationalLevelSeeder menggunakan 'SD', 'SMP', dll di kolom 'name'.
        $levels = DB::table('educational_levels')->get()->keyBy('name');

        $level_sd = $levels['SD'] ?? null;
        $level_smp = $levels['SMP'] ?? null;
        $level_sma = $levels['SMA'] ?? null;
        $level_smk = $levels['SMK'] ?? null;

        // Validasi Jenjang Pendidikan
        if (!$level_sd || !$level_smp || !$level_sma || !$level_smk) {
            $this->command->error('Gagal menjalankan ClassSeeder: Pastikan EducationalLevelSeeder sudah dijalankan dan data (SD, SMP, SMA, SMK) tersedia.');
            return;
        }

        // 2. Ambil ID Jurusan
        $majors = DB::table('majors')->get();

        $major_ipa = $majors->where('description', 'IPA')->first();
        $major_rpl = $majors->where('description', 'RPL')->first();
        $major_akt = $majors->where('description', 'AKT')->first();

        // Validasi Jurusan yang dibutuhkan
        if (!$major_ipa || !$major_rpl || !$major_akt) {
             $missing = [];
             if (!$major_ipa) $missing[] = 'IPA';
             if (!$major_rpl) $missing[] = 'RPL';
             if (!$major_akt) $missing[] = 'AKT';

             $this->command->error('Gagal menjalankan ClassSeeder: Jurusan yang dibutuhkan (' . implode(', ', $missing) . ') tidak ditemukan. Pastikan MajorSeeder sudah dijalankan.');
             return;
        }

        // A. Kelas SD (Grade 1-6) - TANPA Jurusan
        for ($grade = 1; $grade <= 6; $grade++) {
            $classes[] = [
                'name' => "{$grade} A",
                'grade_level' => $grade,
                'educational_level_id' => $level_sd->id,
                'major_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // B. Kelas SMP (Grade 7-9) - TANPA Jurusan
        for ($grade = 7; $grade <= 9; $grade++) {
            $classes[] = [
                'name' => "{$grade} B",
                'grade_level' => $grade,
                'educational_level_id' => $level_smp->id,
                'major_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // C. Kelas SMA (Grade 10-12) - DENGAN Jurusan
        for ($grade = 10; $grade <= 12; $grade++) {
            // Contoh: 10 IPA 1, 11 IPA 2, 12 IPA 3
            for ($index = 1; $index <= 3; $index++) {
                $classes[] = [
                    'name' => "{$grade} {$major_ipa->description} {$index}",
                    'grade_level' => $grade,
                    'educational_level_id' => $level_sma->id,
                    'major_id' => $major_ipa->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // D. Kelas SMK (Grade 10-12) - DENGAN Jurusan
        for ($grade = 10; $grade <= 12; $grade++) {
            // Jurusan RPL (Index 1 & 2)
            for ($index = 1; $index <= 2; $index++) {
                $classes[] = [
                    'name' => "{$grade} {$major_rpl->description} {$index}",
                    'grade_level' => $grade,
                    'educational_level_id' => $level_smk->id,
                    'major_id' => $major_rpl->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            // Jurusan AKUNTANSI (Index 1)
            $classes[] = [
                'name' => "{$grade} {$major_akt->description} 1",
                'grade_level' => $grade,
                'educational_level_id' => $level_smk->id,
                'major_id' => $major_akt->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('classes')->insert($classes);
        $this->command->info(count($classes) . ' classes seeded successfully!');
    }
}
