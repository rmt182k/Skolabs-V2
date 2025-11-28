<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker; // Import Faker

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Inisialisasi Faker
        $faker = Faker::create('id_ID'); // Menggunakan lokal Indonesia

        // 2. Ambil ID role 'teacher'
        $teacherRole = DB::table('roles')->where('name', 'teacher')->first();

        if (!$teacherRole) {
            $this->command->error('Role "teacher" not found. Please run RoleSeeder first.');
            return;
        }

        $totalTeachers = 20; // Jumlah guru yang akan dibuat
        $this->command->info("Creating $totalTeachers realistic teacher data using Faker...");

        // Siapkan array untuk bulk insert
        $usersBatch = [];
        $userRolesBatch = [];
        $userDetailsBatch = [];

        // Tampilkan progress bar
        $this->command->getOutput()->progressStart($totalTeachers);

        // 3. Siapkan data user (tanpa insert, agar bisa bulk insert)
        for ($i = 1; $i <= $totalTeachers; $i++) {
            $usersBatch[] = [
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'email_verified_at' => now(),
                'password' => Hash::make('password'), // password default
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $this->command->getOutput()->progressAdvance();
        }

        // 4. Bulk Insert Users
        // Chunk untuk menghindari error placeholder
        foreach (array_chunk($usersBatch, 250) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        // 5. Ambil semua ID user yang baru dibuat yang emailnya dari batch tadi
        $userEmails = array_column($usersBatch, 'email');
        $createdUsers = DB::table('users')->whereIn('email', $userEmails)->pluck('id');

        // Selesaikan progress bar lama dan mulai yang baru untuk detail
        $this->command->getOutput()->progressFinish();
        $this->command->info('Users created. Now creating details and roles...');
        $this->command->getOutput()->progressStart(count($createdUsers));

        // 6. Siapkan data UserDetails dan UserRoles
        foreach ($createdUsers as $index => $userId) {
            $gender = ($index % 2 == 0) ? 'female' : 'male';

            $userDetailsBatch[] = [
                'user_id' => $userId,
                // Menggunakan NIP (Nomor Induk Pegawai) sebagai contoh
                'identity_number' => 'NIP-' . $faker->unique()->numerify('199#######'),
                // Rentang usia yang wajar untuk guru
                'date_of_birth' => $faker->dateTimeBetween('1970-01-01', '1995-12-31')->format('Y-m-d'),
                'gender' => $gender,
                'phone_number' => $faker->phoneNumber,
                'address' => $faker->address,
                'avatar' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $userRolesBatch[] = [
                'user_id' => $userId,
                'role_id' => $teacherRole->id, // Menggunakan ID role teacher
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->command->getOutput()->progressAdvance();
        }

        // 7. Bulk Insert UserDetails dan UserRoles
        foreach (array_chunk($userDetailsBatch, 250) as $chunk) {
            DB::table('user_details')->insert($chunk);
        }

        foreach (array_chunk($userRolesBatch, 250) as $chunk) {
            DB::table('user_roles')->insert($chunk);
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info("Successfully created $totalTeachers realistic teacher data.");
    }
}
