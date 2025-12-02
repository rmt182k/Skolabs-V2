<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('majors')->truncate();

        // Catatan: Jika Anda menggunakan ID statis (3 dan 4), pastikan EducationalLevelSeeder
        // sudah dijalankan sebelumnya dan ID-nya sesuai.
        // Asumsi: 3 = SMA, 4 = SMK
        $level_sma_id = 3;
        $level_smk_id = 4;

        DB::table('majors')->insert([
            // 🔹 SMA (Senior High School) – educational_level_id = 3
            [
                'educational_level_id' => $level_sma_id,
                'name' => 'Ilmu Pengetahuan Alam',
                'code' => 'IPA',
                'description' => 'Jurusan Ilmu Pengetahuan Alam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_sma_id,
                'name' => 'Ilmu Pengetahuan Sosial',
                'code' => 'IPS',
                'description' => 'Jurusan Ilmu Pengetahuan Sosial',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_sma_id,
                'name' => 'Bahasa dan Budaya',
                'code' => 'BB', // Menggunakan kode singkat yang umum
                'description' => 'Jurusan Bahasa dan Budaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_sma_id,
                'name' => 'Agama',
                'code' => 'AGM',
                'description' => 'Jurusan Keagamaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // 🔹 SMK (Vocational High School) – educational_level_id = 4
            // Bidang Teknologi dan Rekayasa
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Teknik Komputer dan Jaringan',
                'code' => 'TKJ',
                'description' => 'Jurusan Teknik Komputer dan Jaringan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Rekayasa Perangkat Lunak',
                'code' => 'RPL',
                'description' => 'Jurusan Rekayasa Perangkat Lunak',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Multimedia',
                'code' => 'MM',
                'description' => 'Jurusan Multimedia',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Teknik Elektronika Industri',
                'code' => 'TEI',
                'description' => 'Jurusan Teknik Elektronika Industri',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Teknik Otomotif',
                'code' => 'OTO', // Kode umum untuk Otomotif
                'description' => 'Jurusan Teknik Otomotif',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bidang Bisnis dan Manajemen
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Akuntansi dan Keuangan Lembaga',
                'code' => 'AKL', // Kode umum untuk Akuntansi
                'description' => 'Jurusan Akuntansi dan Keuangan Lembaga',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Manajemen Perkantoran dan Layanan Bisnis',
                'code' => 'MPLB',
                'description' => 'Jurusan Manajemen Perkantoran dan Layanan Bisnis',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Pemasaran',
                'code' => 'PM',
                'description' => 'Jurusan Pemasaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bidang Pariwisata
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Perhotelan',
                'code' => 'PH',
                'description' => 'Jurusan Perhotelan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Usaha Perjalanan Wisata',
                'code' => 'UPW',
                'description' => 'Jurusan Usaha Perjalanan Wisata',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bidang Seni dan Kreatif
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Tata Boga',
                'code' => 'TB',
                'description' => 'Jurusan Tata Boga',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Tata Busana',
                'code' => 'BSN', // Diubah dari BB untuk menghindari duplikat
                'description' => 'Jurusan Tata Busana',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bidang Kesehatan dan Farmasi
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Farmasi Klinis dan Komunitas',
                'code' => 'FKK', // Menggunakan FKK agar lebih spesifik
                'description' => 'Jurusan Farmasi Klinis dan Komunitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Asisten Keperawatan',
                'code' => 'AKP',
                'description' => 'Jurusan Asisten Keperawatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bidang Pertanian dan Pangan
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Agribisnis Tanaman Pangan dan Hortikultura',
                'code' => 'ATPH',
                'description' => 'Jurusan Agribisnis Tanaman Pangan dan Hortikultura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Teknologi Pengolahan Hasil Pertanian',
                'code' => 'TPHP',
                'description' => 'Jurusan Teknologi Pengolahan Hasil Pertanian',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bidang Kelautan dan Perikanan
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Teknika Kapal Penangkap Ikan',
                'code' => 'TKPI',
                'description' => 'Jurusan Teknika Kapal Penangkap Ikan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'educational_level_id' => $level_smk_id,
                'name' => 'Nautika Kapal Niaga',
                'code' => 'NKN',
                'description' => 'Jurusan Nautika Kapal Niaga',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

