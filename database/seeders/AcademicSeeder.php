<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class AcademicSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');
        $password = Hash::make('1');

        // Ambil ID referensi
        $academicYearId = DB::table('academic_years')->where('is_active', true)->value('id');
        $sdId = DB::table('educational_levels')->where('name', 'SD')->value('id');
        $smpId = DB::table('educational_levels')->where('name', 'SMP')->value('id');
        $smaId = DB::table('educational_levels')->where('name', 'SMA')->value('id');
        $smkId = DB::table('educational_levels')->where('name', 'SMK')->value('id');

        // --- BAGIAN YANG DIPERBAIKI ---
        // Ambil Mapping Guru & Subject tanpa filter academic_year_id
        $assignments = DB::table('subjects_assignment')->get();

        $teacherMap = [];
        foreach ($assignments as $assign) {
            $teacherMap[$assign->subject_id][] = $assign->user_id;
        }

        // Ambil semua subject ID yang valid (yang punya guru)
        $validSubjectIds = array_keys($teacherMap);

        if (empty($validSubjectIds)) {
            $this->command->error('No subjects assigned to teachers! Please run UserManagementSeeder first.');
            return;
        }

        // Struktur Kelas
        $classesToCreate = [];

        // 1. SD
        for ($grade = 1; $grade <= 6; $grade++) {
            foreach (['A', 'B', 'C'] as $suffix) {
                $classesToCreate[] = [
                    'name' => "{$grade} SD {$suffix}",
                    'grade_level' => $grade,
                    'suffix' => $suffix,
                    'educational_level_id' => $sdId,
                    'major_id' => null,
                    'schedule_end_hour' => 12,
                ];
            }
        }

        // 2. SMP
        for ($grade = 7; $grade <= 9; $grade++) {
            foreach (['A', 'B', 'C'] as $suffix) {
                $classesToCreate[] = [
                    'name' => "{$grade} SMP {$suffix}",
                    'grade_level' => $grade,
                    'suffix' => $suffix,
                    'educational_level_id' => $smpId,
                    'major_id' => null,
                    'schedule_end_hour' => 14,
                ];
            }
        }

        // 3. SMA
        $smaMajors = DB::table('majors')->where('educational_level_id', $smaId)->get();
        for ($grade = 10; $grade <= 12; $grade++) {
            foreach ($smaMajors as $major) {
                $classesToCreate[] = [
                    'name' => "{$grade} {$major->code} 1",
                    'grade_level' => $grade,
                    'suffix' => '1',
                    'educational_level_id' => $smaId,
                    'major_id' => $major->id,
                    'schedule_end_hour' => 15,
                ];
            }
        }

        // 4. SMK
        $smkMajors = DB::table('majors')->where('educational_level_id', $smkId)->get();
        for ($grade = 10; $grade <= 12; $grade++) {
            foreach ($smkMajors as $major) {
                $classesToCreate[] = [
                    'name' => "{$grade} {$major->code} 1",
                    'grade_level' => $grade,
                    'suffix' => '1',
                    'educational_level_id' => $smkId,
                    'major_id' => $major->id,
                    'schedule_end_hour' => 15,
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

            // B. Create Students
            $studentsData = [];
            $userDetailsData = [];
            $userRolesData = [];
            $enrollmentsData = [];

            for ($s = 0; $s < 30; $s++) {
                $studentId = DB::table('users')->insertGetId([
                    'name' => $faker->name,
                    'email' => $faker->unique()->userName . '@student.skolabs.com',
                    'password' => $password,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $userRolesData[] = ['user_id' => $studentId, 'role_id' => 3, 'created_at' => now(), 'updated_at' => now()];
                $userDetailsData[] = [
                    'user_id' => $studentId,
                    'identity_number' => $faker->unique()->numerify('##########'),
                    'gender' => $faker->randomElement(['male', 'female']),
                    'date_of_birth' => $faker->date('Y-m-d', '2015-01-01'),
                    'address' => $faker->address,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $enrollmentsData[] = [
                    'class_id' => $classId,
                    'student_id' => $studentId,
                    'academic_year_id' => $academicYearId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            DB::table('user_roles')->insert($userRolesData);
            DB::table('user_details')->insert($userDetailsData);
            DB::table('class_enrollments')->insert($enrollmentsData);

            // C. Create Schedule
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $schedulesData = [];
            $startHour = 7;
            $endHour = $cls['schedule_end_hour'];

            foreach ($days as $day) {
                for ($h = $startHour; $h < $endHour; $h++) {
                    if ($h == 10 || ($h == 12 && $endHour == 15)) continue;

                    // 1. Pilih Subject secara acak dari list subject yang punya guru
                    $randomSubjectId = $faker->randomElement($validSubjectIds);

                    // 2. Ambil Guru yang valid untuk subject ini
                    $availableTeachers = $teacherMap[$randomSubjectId] ?? [];

                    // Jika entah kenapa kosong, skip
                    if (empty($availableTeachers)) continue;

                    // 3. Pilih satu guru dari yang tersedia
                    $selectedTeacherId = $faker->randomElement($availableTeachers);

                    $schedulesData[] = [
                        'class_id' => $classId,
                        'day_name' => $day,
                        'start_time' => sprintf('%02d:00:00', $h),
                        'end_time' => sprintf('%02d:00:00', $h + 1),
                        'subject_id' => $randomSubjectId,
                        'user_id' => $selectedTeacherId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('class_schedules')->insert($schedulesData);
        }
    }
}
