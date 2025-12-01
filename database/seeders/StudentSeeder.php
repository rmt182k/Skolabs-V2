<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker; // Import Faker

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Inisialisasi Faker
        $faker = Faker::create('id_ID'); // Menggunakan lokal Indonesia untuk data realistis

        // 2. Ambil ID role 'student'
        $studentRole = DB::table('roles')->where('name', 'student')->first();

        if (!$studentRole) {
            $this->command->error('Role "student" not found. Please run RoleSeeder first.');
            return;
        }

        $this->command->info('Creating 810 realistic student data using Faker...');

        // Siapkan array untuk bulk insert
        $usersBatch = [];
        $userRolesBatch = [];
        $userDetailsBatch = [];

        $userIds = []; // Untuk menyimpan ID user yang baru dibuat

        // Tampilkan progress bar
        $this->command->getOutput()->progressStart(810);

        // 3. Siapkan data user (tanpa insert, agar bisa bulk insert)
        for ($i = 1; $i <= 810; $i++) {
            $usersBatch[] = [
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'email_verified_at' => now(),
                'password' => Hash::make('1'), // password default
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
                'identity_number' => 'SID-' . $faker->unique()->numerify('100#####'),
                'date_of_birth' => $faker->dateTimeBetween('2005-01-01', '2010-12-31')->format('Y-m-d'),
                'gender' => $gender,
                'phone_number' => $faker->phoneNumber,
                'address' => $faker->address,
                'avatar' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $userRolesBatch[] = [
                'user_id' => $userId,
                'role_id' => $studentRole->id,
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
        $this->command->info('Successfully created 810 realistic student data.');
    }
}

