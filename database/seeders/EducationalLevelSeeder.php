<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducationalLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('educational_levels')->insert([
            [
                'name' => 'SD',
                'duration_years' => 6,
                'description' => 'Sekolah Dasar (Elementary School)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SMP',
                'duration_years' => 3,
                'description' => 'Sekolah Menengah Pertama (Junior High School)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SMA',
                'duration_years' => 3,
                'description' => 'Sekolah Menengah Atas (Senior High School)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SMK',
                'duration_years' => 3,
                'description' => 'Sekolah Menengah Kejuruan (Vocational High School)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
