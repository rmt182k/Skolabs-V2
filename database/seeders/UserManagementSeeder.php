<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserManagementSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');
        $password = Hash::make('1'); // Password default '1'

        // Ambil data referensi
        $subjectIds = DB::table('subjects')->pluck('id')->toArray();

        // --- 1. ADMINS ---
        $admins = [
            [
                'name' => 'Roy Martogi Tamba',
                'email' => 'roymartogit@gmail.com',
            ],
            [
                'name' => 'Muhammad Fakhran Hadyan',
                'email' => 'fakhran@gmail.com',
            ],
        ];

        foreach ($admins as $adminData) {
            $userId = DB::table('users')->insertGetId([
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => $password,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign Role Admin (ID 1)
            DB::table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create Detail
            DB::table('user_details')->insert([
                'user_id' => $userId,
                'gender' => 'male',
                'address' => 'Admin Office',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // --- 2. TEACHERS (20 Guru) ---
        for ($i = 1; $i <= 20; $i++) {
            $userId = DB::table('users')->insertGetId([
                'name' => $faker->name,
                'email' => "teacher{$i}@skolabs.com",
                'password' => $password,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign Role Teacher (ID 2)
            DB::table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('user_details')->insert([
                'user_id' => $userId,
                'identity_number' => $faker->nik,
                'phone_number' => $faker->phoneNumber,
                'gender' => $faker->randomElement(['male', 'female']),
                'address' => $faker->address,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // --- BAGIAN YANG DIPERBAIKI ---
            // Setiap guru mengajar 1 sampai 3 mata pelajaran random
            $assignedSubjects = $faker->randomElements($subjectIds, rand(1, 3));

            foreach ($assignedSubjects as $subId) {
                // Kita insertSubject Assignment TANPA academic_year_id sesuai skema kamu
                DB::table('subjects_assignment')->insertOrIgnore([
                    'user_id' => $userId,
                    'subject_id' => $subId,
                    // 'academic_year_id' => $academicYearId, // <--- Baris ini DIHAPUS karena kolom tidak ada
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Teachers created and subjects assigned successfully.');
    }
}
