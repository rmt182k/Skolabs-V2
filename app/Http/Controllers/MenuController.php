<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{
    // ... Bagian Awal Controller tidak ada perubahan ...

    /**
     * Menampilkan kerangka halaman manajemen menu.
     */
    public function index()
    {
        return view('menu.index');
    }

    /**
     * API untuk mengambil semua menu dalam format hierarkis.
     */
    public function fetchMenus()
    {
        try {
            $allMenus = DB::table('menus')->orderBy('order', 'asc')->get();

            if ($allMenus->isEmpty()) {
                return response()->json(['success' => true, 'message' => 'No menus found.', 'data' => []]);
            }

            $menuMap = [];
            foreach ($allMenus as $menu) {
                $menu->children = [];
                $menuMap[$menu->id] = $menu;
            }

            $tree = [];
            foreach ($menuMap as $id => &$menu) {
                if ($menu->parent_id !== null && isset($menuMap[$menu->parent_id])) {
                    $menuMap[$menu->parent_id]->children[] = &$menu;
                } else {
                    $tree[] = &$menu;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Menus retrieved successfully.',
                'data' => $tree
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching menus: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred while fetching menus.'], 500);
        }
    }

    /**
     * Menyimpan menu baru.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'url' => 'nullable|string|max:255',
                'icon' => 'nullable|string|max:255',
                'parent_id' => 'nullable|exists:menus,id',
                'is_active' => 'required|boolean',
            ]);

            if (!empty($validatedData['parent_id'])) {
                $maxOrder = DB::table('menus')->where('parent_id', $validatedData['parent_id'])->max('order');
            } else {
                $maxOrder = DB::table('menus')->whereNull('parent_id')->max('order');
            }

            $menuId = DB::table('menus')->insertGetId([
                'title' => $validatedData['title'],
                'url' => $validatedData['url'] ?? '#',
                'icon' => $validatedData['icon'] ?? 'fas fa-circle',
                'parent_id' => $validatedData['parent_id'] ?? null,
                'is_active' => $validatedData['is_active'],
                'order' => ($maxOrder ?? 0) + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $newMenu = DB::table('menus')->find($menuId);

            return response()->json([
                'success' => true,
                'message' => 'Menu created successfully.',
                'data' => $newMenu
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating menu: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while creating the menu.'
            ], 500);
        }
    }

    /**
     * Mengupdate data spesifik sebuah menu.
     */
    public function update(Request $request, $id)
    {
        try {
            if (!DB::table('menus')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Menu not found.'], 404);
            }

            $validatedData = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'url' => 'sometimes|nullable|string|max:255',
                'icon' => 'sometimes|nullable|string|max:255',
                'parent_id' => 'sometimes|nullable|exists:menus,id',
                'is_active' => 'sometimes|required|boolean',
            ]);

            if (isset($validatedData['parent_id']) && $validatedData['parent_id'] == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'A menu cannot be its own parent.'
                ], 422);
            }
            if (isset($validatedData['parent_id']) && $validatedData['parent_id'] && $this->isDescendant($id, $validatedData['parent_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot set parent to a descendant menu (circular reference).'
                ], 422);
            }

            $validatedData['updated_at'] = now();
            DB::table('menus')->where('id', $id)->update($validatedData);

            $updatedMenu = DB::table('menus')->find($id);

            return response()->json([
                'success' => true,
                'message' => 'Menu updated successfully.',
                'data' => $updatedMenu
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating menu ID ' . $id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while updating the menu.'
            ], 500);
        }
    }

    /**
     * Menyimpan urutan dan struktur menu yang baru.
     */
    public function updateOrder(Request $request)
    {
        try {
            $validated = $request->validate(['menu' => 'required|array']);

            DB::beginTransaction();
            $this->saveOrderRecursive($validated['menu']);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Menu order saved successfully.'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data format.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating menu order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. The order has not been saved.'
            ], 500);
        }
    }

    private function saveOrderRecursive(array $menuItems, $parentId = null)
    {
        foreach ($menuItems as $order => $item) {
            if (!isset($item['id']))
                continue;

            DB::table('menus')->where('id', $item['id'])->update([
                'order' => $order + 1,
                'parent_id' => $parentId,
                'updated_at' => now(),
            ]);

            if (!empty($item['children'])) {
                $this->saveOrderRecursive($item['children'], $item['id']);
            }
        }
    }

    private function isDescendant($menuId, $targetId)
    {
        $children = DB::table('menus')->where('parent_id', $menuId)->pluck('id');
        if ($children->contains($targetId)) {
            return true;
        }
        foreach ($children as $childId) {
            if ($this->isDescendant($childId, $targetId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Menghapus sebuah menu.
     */
    public function destroy($id)
    {
        try {
            if (!DB::table('menus')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Menu not found.'], 404);
            }
            if (DB::table('menus')->where('parent_id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Cannot delete menu. Please delete or reassign child menus first.'], 409);
            }

            DB::beginTransaction();
            DB::table('role_menu_permissions')->where('menu_id', $id)->delete();
            DB::table('user_menu_overrides')->where('menu_id', $id)->delete();
            DB::table('menus')->where('id', $id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Menu deleted successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting menu ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred while deleting the menu.'], 500);
        }
    }

    // ==========================================
    // PERMISSION & ACCESS CONTROL METHODS
    // ==========================================

    /**
     * API: Get all access details for a specific menu.
     */
    public function getAccessDetails($menuId)
    {
        try {
            $menu = DB::table('menus')->find($menuId);
            if (!$menu) {
                return response()->json(['success' => false, 'message' => 'Menu not found.'], 404);
            }

            // 1. Ambil role mana saja yang terhubung ke menu ini di `role_menus`
            $rolesWithAccess = DB::table('role_menus')->where('menu_id', $menuId)->pluck('role_id')->toArray();

            // 2. Ambil izin spesifik dari `role_menu_permissions`
            $rolePermissions = DB::table('role_menu_permissions')
                ->join('permissions', 'role_menu_permissions.permission_id', '=', 'permissions.id')
                ->where('role_menu_permissions.menu_id', $menuId)
                ->select('role_menu_permissions.role_id', 'permissions.name as permission_name')
                ->get()
                ->groupBy('role_id')
                ->map(fn($items, $role_id) => ['role_id' => (int) $role_id, 'permissions' => $items->pluck('permission_name')->all()])
                ->values();

            // 3. Ambil user menu access overrides
            $userMenuOverrides = DB::table('user_menu_overrides')
                ->join('users', 'user_menu_overrides.user_id', '=', 'users.id')
                ->where('user_menu_overrides.menu_id', $menuId)
                ->select('users.id', 'users.name', 'users.email', 'user_menu_overrides.access_type')
                ->get();

            // 4. Ambil user permission overrides
            $userPermissionOverrides = DB::table('user_menu_permission_overrides')
                ->join('users', 'user_menu_permission_overrides.user_id', '=', 'users.id')
                ->join('permissions', 'user_menu_permission_overrides.permission_id', '=', 'permissions.id')
                ->where('user_menu_permission_overrides.menu_id', $menuId)
                ->select('user_menu_permission_overrides.id', 'users.name as user_name', 'permissions.name as permission_name', 'user_menu_permission_overrides.access_type')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'menu' => $menu,
                    'roles_with_access' => $rolesWithAccess,
                    'role_permissions' => $rolePermissions,
                    'user_menu_overrides' => $userMenuOverrides,
                    'user_permission_overrides' => $userPermissionOverrides,
                ]
            ]);
        } catch (Exception $e) {
            Log::error("Error fetching access details: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch access details.' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ BARU: Update role permissions untuk menu tertentu (view, create, edit, etc).
     */
    public function updateRolePermissions(Request $request, $menuId)
    {
        try {
            $validated = $request->validate([
                'roles_with_access' => 'present|array',
                'roles_with_access.*' => 'integer|exists:roles,id',
                'permissions_by_role' => 'present|array',
                'permissions_by_role.*.role_id' => 'required|integer|exists:roles,id',
                'permissions_by_role.*.permissions' => 'present|array'
            ]);
            $validPermissionsMap = DB::table('permissions')->pluck('id', 'name');

            DB::beginTransaction();
            // 1. Update `role_menus`
            DB::table('role_menus')->where('menu_id', $menuId)->delete();
            $roleMenuInserts = array_map(fn($roleId) => [
                'role_id' => $roleId,
                'menu_id' => $menuId,
                'created_at' => now(),
                'updated_at' => now()
            ], $validated['roles_with_access']);
            if (!empty($roleMenuInserts))
                DB::table('role_menus')->insert($roleMenuInserts);

            // 2. Update `role_menu_permissions` (nama tabel baru)
            DB::table('role_menu_permissions')->where('menu_id', $menuId)->delete();
            $permissionInserts = [];
            foreach ($validated['permissions_by_role'] as $roleData) {
                foreach ($roleData['permissions'] as $permissionName) {
                    if (isset($validPermissionsMap[$permissionName])) {
                        $permissionInserts[] = [
                            'role_id' => $roleData['role_id'],
                            'menu_id' => $menuId,
                            'permission_id' => $validPermissionsMap[$permissionName],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }
            if (!empty($permissionInserts))
                DB::table('role_menu_permissions')->insert($permissionInserts);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Role access and permissions updated successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error updating role permissions: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update role permissions.'], 500);
        }
    }

    // ✅ FUNGSI BARU
    public function updateUserPermissionOverride(Request $request, $menuId)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'permission_id' => 'required|exists:permissions,id',
                'type' => 'required|in:grant,revoke' // 'type' dari form di JS
            ]);

            // --- PERBAIKAN DIMULAI (BUG 1) ---
            // Mengganti 'type' menjadi 'access_type' sesuai nama kolom di DB
            $dataToInsertOrUpdate = [
                'access_type' => $validated['type'],
                'updated_at' => now()
            ];

            // Menggunakan `updateOrInsert` dengan menambahkan `created_at` saat insert.
            // Metode ini memastikan `created_at` hanya di-set saat data baru dibuat.
            DB::table('user_menu_permission_overrides')->updateOrInsert(
                [
                    'user_id' => $validated['user_id'],
                    'menu_id' => $menuId,
                    'permission_id' => $validated['permission_id']
                ],
                // Dengan menambahkan `created_at` di sini, Laravel akan menggunakannya
                // saat membuat record baru. Pada saat update, `created_at` akan diabaikan
                // karena kolomnya sudah ada.
                array_merge($dataToInsertOrUpdate, ['created_at' => now()])
            );
            // --- PERBAIKAN SELESAI (BUG 1) ---

            return response()->json(['success' => true, 'message' => 'User permission override saved.']);
        } catch (Exception $e) {
            Log::error("Error updating user permission override: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save override.'
            ], 500);
        }
    }

    // ✅ FUNGSI BARU
    public function deleteUserPermissionOverride($menuId, $overrideId)
    {
        try {
            DB::table('user_menu_permission_overrides')->where('id', $overrideId)->where('menu_id', $menuId)->delete();
            return response()->json(['success' => true, 'message' => 'User permission override removed.']);
        } catch (Exception $e) {
            Log::error("Error deleting user permission override: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove override.'], 500);
        }
    }

    /**
     * Add atau update user override.
     */
    public function updateUserOverride(Request $request, $menuId)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'access_type' => 'required|in:grant,revoke'
            ]);

            // --- PERBAIKAN DIMULAI (BUG 1) ---
            DB::table('user_menu_overrides')->updateOrInsert(
                [
                    'user_id' => $validated['user_id'],
                    'menu_id' => $menuId
                ],
                [
                    'access_type' => $validated['access_type'],
                    'updated_at' => now(),
                    'created_at' => now() // Tambahkan created_at
                ]
            );
            // --- PERBAIKAN SELESAI (BUG 1) ---

            return response()->json(['success' => true, 'message' => 'User override updated successfully.']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error updating user override: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update user override.'], 500);
        }
    }

    /**
     * Delete user override.
     */
    public function deleteUserOverride($menuId, $userId)
    {
        try {
            $deleted = DB::table('user_menu_overrides')
                ->where('menu_id', $menuId)
                ->where('user_id', $userId)
                ->delete();

            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'Override not found.'], 404);
            }
            return response()->json(['success' => true, 'message' => 'User override removed successfully.']);
        } catch (Exception $e) {
            Log::error('Error deleting user override: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete user override.'], 500);
        }
    }

    // ... Sisa Controller tidak ada perubahan ...

    /**
     * Get all roles untuk dropdown.
     */
    public function getRoles()
    {
        try {
            $roles = DB::table('roles')->select('id', 'name')->get();
            return response()->json(['success' => true, 'data' => $roles]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch roles.'], 500);
        }
    }

    /**
     * Search users untuk override.
     */
    public function searchUsers(Request $request)
    {
        try {
            $query = $request->input('q', '');
            $users = DB::table('users')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->select('id', 'name', 'email')
                ->limit(10)
                ->get();

            return response()->json(['success' => true, 'data' => $users]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to search users.'], 500);
        }
    }

    /**
     * API untuk mengambil menu yang bisa diakses oleh user yang sedang login.
     * Digunakan untuk membangun sidebar navigasi.
     */
    public function fetchUserMenus(Request $request)
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
            }

            // --- Persiapan Data (Lebih Efisien) ---
            $allMenus = DB::table('menus')->where('is_active', true)->orderBy('order', 'asc')->get()->keyBy('id');
            $permissionsMap = DB::table('permissions')->pluck('name', 'id');

            // Ambil semua override menu & izin untuk user ini sekali saja
            $menuAccessOverrides = DB::table('user_menu_overrides')
                ->where('user_id', $userId)
                ->pluck('access_type', 'menu_id');

            $permissionOverrides = DB::table('user_menu_permission_overrides')
                ->where('user_id', $userId)
                ->get()
                ->groupBy('menu_id');


            // --- Langkah 1: Dapatkan Role Pengguna & Akses Menu Dasar ---
            $userRoleIds = DB::table('user_roles')->where('user_id', $userId)->pluck('role_id')->toArray();
            $baseMenuIds = [];

            if (!empty($userRoleIds)) {
                $baseMenuIds = DB::table('role_menus')
                    ->whereIn('role_id', $userRoleIds)
                    ->distinct()
                    ->pluck('menu_id')
                    ->toArray();
            }

            // --- Langkah 2: Terapkan Override Akses Menu ---
            $finalMenuIds = $baseMenuIds;
            foreach ($menuAccessOverrides as $menuId => $type) {
                if ($type === 'grant' && !in_array($menuId, $finalMenuIds)) {
                    $finalMenuIds[] = $menuId; // Tambahkan menu
                } elseif ($type === 'revoke') {
                    $finalMenuIds = array_diff($finalMenuIds, [$menuId]); // Hapus menu
                }
            }

            // Jika setelah semua proses tidak ada menu yang bisa diakses
            if (empty($finalMenuIds)) {
                return response()->json(['success' => true, 'data' => []]);
            }

            // --- Langkah 3: Kumpulkan Izin Berdasarkan Role ---
            $rolePermissions = [];
            if (!empty($userRoleIds)) {
                $rolePermissions = DB::table('role_menu_permissions')
                    ->whereIn('role_id', $userRoleIds)
                    ->whereIn('menu_id', $finalMenuIds)
                    ->get()
                    ->groupBy('menu_id');
            }

            // --- Langkah 4: Bangun Menu yang Dapat Diakses Beserta Izin Final ---
            $accessibleMenus = [];
            foreach ($finalMenuIds as $menuId) {
                if (!isset($allMenus[$menuId]))
                    continue; // Lewati jika menu tidak aktif

                // Kumpulkan izin dasar dari role
                $basePerms = [];
                if (isset($rolePermissions[$menuId])) {
                    foreach ($rolePermissions[$menuId] as $perm) {
                        if (isset($permissionsMap[$perm->permission_id])) {
                            $basePerms[] = $permissionsMap[$perm->permission_id];
                        }
                    }
                }
                $finalPermissions = array_unique($basePerms);

                // Terapkan override izin
                if (isset($permissionOverrides[$menuId])) {
                    foreach ($permissionOverrides[$menuId] as $override) {
                        // **PERBAIKAN UTAMA: Cek apakah properti 'access_type' ada**
                        if (isset($override->access_type) && isset($override->permission_id)) {
                            $permissionName = $permissionsMap[$override->permission_id] ?? null;
                            if (!$permissionName)
                                continue;

                            if ($override->access_type === 'grant' && !in_array($permissionName, $finalPermissions)) {
                                $finalPermissions[] = $permissionName;
                            } elseif ($override->access_type === 'revoke') {
                                $finalPermissions = array_diff($finalPermissions, [$permissionName]);
                            }
                        }
                    }
                }

                $menu = clone $allMenus[$menuId];
                $menu->permissions = array_values($finalPermissions);
                $accessibleMenus[$menuId] = $menu;
            }

            // --- Langkah 5: Membangun Struktur Pohon (Tree) ---
            $requiredParentIds = $this->getAllParentIds($allMenus, array_keys($accessibleMenus));
            foreach ($requiredParentIds as $parentId) {
                if (!isset($accessibleMenus[$parentId]) && isset($allMenus[$parentId])) {
                    $parentMenu = clone $allMenus[$parentId];
                    $parentMenu->permissions = [];
                    $accessibleMenus[$parentId] = $parentMenu;
                }
            }

            $finalAccessibleMenus = [];
            foreach ($accessibleMenus as $menuId => $menu) {
                $hasChildrenInList = false;
                foreach ($accessibleMenus as $childCheck) {
                    if ($childCheck->parent_id == $menuId) {
                        $hasChildrenInList = true;
                        break;
                    }
                }

                if (!empty($menu->permissions) || $hasChildrenInList || ($menu->url && $menu->url !== '#')) {
                    $finalAccessibleMenus[$menuId] = $menu;
                }
            }

            $tree = [];
            foreach ($finalAccessibleMenus as $id => &$menu) {
                $menu->children = []; // Inisialisasi children
                if ($menu->parent_id && isset($finalAccessibleMenus[$menu->parent_id])) {
                    $finalAccessibleMenus[$menu->parent_id]->children[] = &$menu;
                } elseif (!$menu->parent_id) {
                    $tree[] = &$menu;
                }
            }
            unset($menu);


            $sortedTree = $this->sortChildrenRecursive(array_values($tree));

            return response()->json(['success' => true, 'data' => $sortedTree]);
        } catch (Exception $e) {
            Log::error('Error fetching user menus: ' . $e->getMessage() . ' on line ' . $e->getLine() . ' in file ' . $e->getFile());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching menus. Please contact support.'
            ], 500);
        }
    }


    /** Helper untuk mendapatkan semua ID parent dari menu. */
    private function getAllParentIds($allMenus, $childIds)
    {
        $parents = [];
        $queue = $childIds;
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            if (isset($allMenus[$currentId]) && $allMenus[$currentId]->parent_id) {
                $parentId = $allMenus[$currentId]->parent_id;
                if (!in_array($parentId, $parents)) {
                    $parents[] = $parentId;
                    $queue[] = $parentId;
                }
            }
        }
        return $parents;
    }

    /** Helper untuk mengurutkan anak menu secara rekursif */
    private function sortChildrenRecursive($menuArray)
    {
        if (!is_array($menuArray))
            return [];

        usort($menuArray, fn ($a, $b) => $a->order <=> $b->order);
        foreach ($menuArray as $item) {
            if (isset($item->children) && is_array($item->children) && !empty($item->children)) {
                $item->children = $this->sortChildrenRecursive($item->children);
            }
        }
        return $menuArray;
    }
}
