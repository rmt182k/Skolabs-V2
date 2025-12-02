<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk membuat user default dengan role ADMIN.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $adminUsers = [
            [
                'name' => 'Roy Martogi Tamba',
                'email' => 'roymartogit@gmail.com',
                'password' => '1',
            ],
            [
                'name' => 'Muhammad Fakhran Hadyan',
                'email' => 'fakhran@gmail.com',
                'password' => '1',
            ],
        ];

        foreach ($adminUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );

            UserRole::firstOrCreate([
                'user_id' => $user->id,
                'role_id' => $adminRole->id,
            ]);

        }
    }
}
