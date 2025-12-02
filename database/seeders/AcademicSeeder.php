<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AcademicSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');
        $password = Hash::make('1');

        // Ambil ID referensi
        $academicYearId = DB::table('academic_years')->where('is_active', true)->value('id');
        $sdId = DB::table('educational_levels')->where('name', 'SD')->value('id');
        $smaId = DB::table('educational_levels')->where('name', 'SMA')->value('id');
        $smkId = DB::table('educational_levels')->where('name', 'SMK')->value('id');

        $teacherIds = DB::table('user_roles')->where('role_id', 2)->pluck('user_id')->toArray();
        $subjectIds = DB::table('subjects')->pluck('id')->toArray();

        // Struktur Kelas yang akan dibuat
        $classesToCreate = [];

        // 1. SD (Kelas 1-6, Paralel A, B, C)
        for ($grade = 1; $grade <= 6; $grade++) {
            foreach (['A', 'B', 'C'] as $suffix) {
                $classesToCreate[] = [
                    'name' => "{$grade} SD {$suffix}",
                    'grade_level' => $grade,
                    'suffix' => $suffix,
                    'educational_level_id' => $sdId,
                    'major_id' => null,
                    'schedule_end_hour' => 12, // Jam 12:00
                ];
            }
        }

        // 2. SMA (Kelas 10-12, Jurusan IPA, IPS, BHS)
        $smaMajors = DB::table('majors')->where('educational_level_id', $smaId)->get();
        for ($grade = 10; $grade <= 12; $grade++) {
            foreach ($smaMajors as $major) {
                $classesToCreate[] = [
                    'name' => "{$grade} {$major->code} 1", // Contoh: 10 MIPA 1
                    'grade_level' => $grade,
                    'suffix' => '1',
                    'educational_level_id' => $smaId,
                    'major_id' => $major->id,
                    'schedule_end_hour' => 15, // Jam 15:00
                ];
            }
        }

        // 3. SMK (Kelas 10-12, 5 Jurusan)
        $smkMajors = DB::table('majors')->where('educational_level_id', $smkId)->get();
        for ($grade = 10; $grade <= 12; $grade++) {
            foreach ($smkMajors as $major) {
                $classesToCreate[] = [
                    'name' => "{$grade} {$major->code} 1",
                    'grade_level' => $grade,
                    'suffix' => '1',
                    'educational_level_id' => $smkId,
                    'major_id' => $major->id,
                    'schedule_end_hour' => 15, // Jam 15:00
                ];
            }
        }

        // --- EKSEKUSI PEMBUATAN KELAS, SISWA & JADWAL ---

        foreach ($classesToCreate as $cls) {
            // A. Create Class
            $classId = DB::table('classes')->insertGetId([
                'name' => $cls['name'],
                'suffix' => $cls['suffix'],
                'grade_level' => $cls['grade_level'],
                'educational_level_id' => $cls['educational_level_id'],
                'major_id' => $cls['major_id'],
                'academic_year_id' => $academicYearId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info("Created Class: {$cls['name']}... seeding 30 students...");

            // B. Create 30 Students for this class
            $studentsData = [];
            $userDetailsData = [];
            $userRolesData = [];
            $enrollmentsData = [];

            for ($s = 0; $s < 30; $s++) {
                // Insert User (Satu per satu untuk mendapatkan ID)
                $studentId = DB::table('users')->insertGetId([
                    'name' => $faker->name,
                    'email' => $faker->unique()->userName . '@student.skolabs.com', // Fake email
                    'password' => $password,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Prepare Data Role & Detail
                $userRolesData[] = [
                    'user_id' => $studentId,
                    'role_id' => 3, // Student
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $userDetailsData[] = [
                    'user_id' => $studentId,
                    'identity_number' => $faker->unique()->numerify('##########'), // NISN
                    'gender' => $faker->randomElement(['male', 'female']),
                    'date_of_birth' => $faker->date('Y-m-d', '2015-01-01'),
                    'address' => $faker->address,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Prepare Enrollment
                $enrollmentsData[] = [
                    'class_id' => $classId,
                    'student_id' => $studentId,
                    'academic_year_id' => $academicYearId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk Insert Data Pendukung Siswa
            DB::table('user_roles')->insert($userRolesData);
            DB::table('user_details')->insert($userDetailsData);
            DB::table('class_enrollments')->insert($enrollmentsData);

            // C. Create Schedule (Senin - Sabtu)
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $schedulesData = [];

            // Start jam 07:00
            $startHour = 7;
            $endHour = $cls['schedule_end_hour']; // 12 atau 15

            foreach ($days as $day) {
                // Loop per jam pelajaran (asumsi 1 jam per mapel untuk testing)
                for ($h = $startHour; $h < $endHour; $h++) {
                    // Skip istirahat (misal jam 10 dan jam 12)
                    if ($h == 10 || ($h == 12 && $endHour == 15)) continue;

                    $schedulesData[] = [
                        'class_id' => $classId,
                        'day_name' => $day,
                        'start_time' => sprintf('%02d:00:00', $h),
                        'end_time' => sprintf('%02d:00:00', $h + 1),
                        'subject_id' => $faker->randomElement($subjectIds), // Acak Mapel
                        'user_id' => $faker->randomElement($teacherIds),   // Acak Guru
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('class_schedules')->insert($schedulesData);
        }
    }
}
