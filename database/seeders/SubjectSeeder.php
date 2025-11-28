<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('subjects')->insert([
            // ======================================
            // 🔹 SD (Sekolah Dasar)
            // ======================================
            [
                'name' => 'Pendidikan Agama dan Budi Pekerti',
                'code' => 'SD-PAI',
                'description' => 'Pelajaran agama sesuai keyakinan siswa dan pembentukan karakter di tingkat SD.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)',
                'code' => 'SD-PPKN',
                'description' => 'Menanamkan nilai-nilai Pancasila dan kewarganegaraan dasar.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bahasa Indonesia',
                'code' => 'SD-BINDO',
                'description' => 'Kemampuan membaca, menulis, mendengar, dan berbicara bahasa Indonesia.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Matematika',
                'code' => 'SD-MTK',
                'description' => 'Dasar-dasar berhitung, geometri, dan konsep bilangan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ilmu Pengetahuan Alam (IPA)',
                'code' => 'SD-IPA',
                'description' => 'Pengenalan dasar sains, lingkungan, dan alam sekitar.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ilmu Pengetahuan Sosial (IPS)',
                'code' => 'SD-IPS',
                'description' => 'Pemahaman tentang masyarakat, sejarah, dan lingkungan sosial.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Seni Budaya dan Prakarya (SBdP)',
                'code' => 'SD-SBDP',
                'description' => 'Pengembangan kreativitas dalam seni, musik, tari, dan keterampilan tangan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)',
                'code' => 'SD-PJOK',
                'description' => 'Aktivitas fisik dan pemahaman kesehatan diri.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ======================================
            // 🔹 SMP (Sekolah Menengah Pertama)
            // ======================================
            [
                'name' => 'Pendidikan Agama dan Budi Pekerti',
                'code' => 'SMP-PAI',
                'description' => 'Pelajaran agama dan moral di tingkat SMP.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)',
                'code' => 'SMP-PPKN',
                'description' => 'Pemahaman nilai-nilai Pancasila, hak dan kewajiban warga negara.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bahasa Indonesia',
                'code' => 'SMP-BINDO',
                'description' => 'Kemampuan berbahasa dan bersastra dalam konteks yang lebih luas.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bahasa Inggris',
                'code' => 'SMP-BING',
                'description' => 'Bahasa Inggris dasar untuk komunikasi global.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Matematika',
                'code' => 'SMP-MTK',
                'description' => 'Konsep aljabar, geometri, dan aritmetika lanjutan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ilmu Pengetahuan Alam (IPA)',
                'code' => 'SMP-IPA',
                'description' => 'Pembelajaran fisika, kimia, dan biologi dasar.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ilmu Pengetahuan Sosial (IPS)',
                'code' => 'SMP-IPS',
                'description' => 'Geografi, sejarah, ekonomi, dan sosiologi dasar.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Seni Budaya',
                'code' => 'SMP-SENBUD',
                'description' => 'Seni musik, rupa, tari, dan teater di tingkat menengah.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)',
                'code' => 'SMP-PJOK',
                'description' => 'Kesehatan jasmani, olahraga, dan kebugaran.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Prakarya',
                'code' => 'SMP-PRAK',
                'description' => 'Kegiatan keterampilan dan wirausaha dasar.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Informatika',
                'code' => 'SMP-INFO',
                'description' => 'Dasar komputer dan teknologi informasi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ======================================
            // 🔹 SMA (Sekolah Menengah Atas)
            // ======================================
            [
                'name' => 'Pendidikan Agama dan Budi Pekerti',
                'code' => 'SMA-PAI',
                'description' => 'Pendalaman nilai-nilai agama dan karakter.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)',
                'code' => 'SMA-PPKN',
                'description' => 'Pendidikan moral, hukum, dan kewarganegaraan lanjutan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bahasa Indonesia',
                'code' => 'SMA-BINDO',
                'description' => 'Kemampuan menulis ilmiah, analisis sastra, dan komunikasi efektif.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bahasa Inggris',
                'code' => 'SMA-BING',
                'description' => 'Komunikasi bahasa Inggris akademik dan profesional.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Matematika',
                'code' => 'SMA-MTK',
                'description' => 'Aljabar, trigonometri, kalkulus dasar, dan logika matematika.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fisika',
                'code' => 'SMA-FIS',
                'description' => 'Konsep energi, gerak, dan hukum fisika dasar.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kimia',
                'code' => 'SMA-KIM',
                'description' => 'Struktur zat, reaksi kimia, dan penerapannya.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Biologi',
                'code' => 'SMA-BIO',
                'description' => 'Ilmu tentang makhluk hidup dan sistem kehidupan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sejarah Indonesia',
                'code' => 'SMA-SEJID',
                'description' => 'Perjalanan sejarah bangsa Indonesia.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Geografi',
                'code' => 'SMA-GEO',
                'description' => 'Studi tentang bumi dan fenomena alam sosial.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sosiologi',
                'code' => 'SMA-SOS',
                'description' => 'Studi perilaku sosial dan dinamika masyarakat.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ekonomi',
                'code' => 'SMA-EKO',
                'description' => 'Konsep ekonomi, bisnis, dan keuangan dasar.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Informatika',
                'code' => 'SMA-INFO',
                'description' => 'Pemrograman dasar dan sistem informasi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Seni Budaya',
                'code' => 'SMA-SENBUD',
                'description' => 'Seni musik, rupa, tari, dan teater di tingkat SMA.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)',
                'code' => 'SMA-PJOK',
                'description' => 'Pendidikan jasmani dan gaya hidup sehat.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ======================================
            // 🔹 SMK (Sekolah Menengah Kejuruan)
            // ======================================
            [
                'name' => 'Pendidikan Agama dan Budi Pekerti',
                'code' => 'SMK-PAI',
                'description' => 'Nilai-nilai agama dan karakter untuk siswa vokasi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bahasa Indonesia',
                'code' => 'SMK-BINDO',
                'description' => 'Komunikasi efektif dalam konteks profesional.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bahasa Inggris',
                'code' => 'SMK-BING',
                'description' => 'Bahasa Inggris untuk komunikasi bisnis dan teknis.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Matematika',
                'code' => 'SMK-MTK',
                'description' => 'Aplikasi matematika dalam bidang kejuruan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'IPA Terapan',
                'code' => 'SMK-IPA',
                'description' => 'Sains terapan untuk bidang teknik dan vokasi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kewirausahaan',
                'code' => 'SMK-KWU',
                'description' => 'Konsep bisnis, pemasaran, dan manajemen usaha kecil.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Simulasi dan Komunikasi Digital',
                'code' => 'SMK-SKD',
                'description' => 'Komunikasi digital dan etika profesional di dunia kerja.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Produk Kreatif dan Kewirausahaan (PKK)',
                'code' => 'SMK-PKK',
                'description' => 'Proyek nyata dalam menciptakan produk dan bisnis.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
