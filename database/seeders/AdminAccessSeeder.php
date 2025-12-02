<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminAccessSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('role_menus')->truncate();

        // 1. Ambil semua menu yang ada
        $allMenus = DB::table('menus')->pluck('id');

        // 2. Assign SEMUA menu ke Admin (Role ID 1)
        foreach ($allMenus as $menuId) {
            DB::table('role_menus')->insert([
                'role_id' => 1, // Admin
                'menu_id' => $menuId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. (Opsional) Jika kamu pakai tabel role_menu_permissions,
        // Kita bisa kosongkan dulu, atau beri Admin akses full.
        // Untuk amannya, kita beri Admin akses full permission juga biar demo lancar saat pakai akun Admin.

        DB::table('role_menu_permissions')->truncate();

        /* NOTE: Untuk demo "menambah permission", kamu bisa comment bagian bawah ini
           jika ingin Admin juga di-set manual. Tapi biasanya Admin butuh akses awal
           untuk membuka menu setting. Jadi saya sarankan Admin diberi akses full dulu.
        */

        $allPermissions = DB::table('permissions')->pluck('id');
        foreach ($allMenus as $menuId) {
            foreach ($allPermissions as $permId) {
                 // Logic sederhana: Admin punya semua permission di semua menu
                 // Nanti di aplikasi kamu filter sendiri mana permission yang relevan
                 DB::table('role_menu_permissions')->insertOrIgnore([
                     'role_id' => 1,
                     'menu_id' => $menuId,
                     'permission_id' => $permId,
                     'created_at' => now(),
                     'updated_at' => now(),
                 ]);
            }
        }

        $this->command->info('Access Control Seeded: Admin has ALL Menus. Teacher & Student have NONE.');
    }
}
