<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function index()
    {
        return view('role.index');
    }

    /**
     * API: Mengambil daftar semua role dengan paginasi, pencarian, dan pengurutan
     */
    public function fetchRoles(Request $request)
    {
        try {
            $query = DB::table('roles')->select(
                'id',
                'name',
                'display_name',
                'description',
                'badge_color',
                'is_active',
                'created_at',
                'updated_at'
            );

            // Fitur Pengurutan
            $sortBy = $request->input('sort_by', 'display_name'); // Default sort by display_name
            $sortDirection = $request->input('sort_dir', 'asc'); // Default sort direction
            if (!in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'asc';
            }
            $query->orderBy($sortBy, $sortDirection);


            // Mengambil semua data yang cocok tanpa paginasi
            $roles = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Roles fetched successfully.',
                'data' => $roles
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching roles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching roles.'
            ], 500);
        }
    }

    /**
     * API: Mengambil satu role berdasarkan ID
     */
    public function show($id)
    {
        try {
            $role = DB::table('roles')->find($id);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role fetched successfully.',
                'data' => $role
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching role ID ' . $id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching the role.'
            ], 500);
        }
    }

    /**
     * API: Mencari role berdasarkan nama atau display name
     */
    public function search(Request $request)
    {
        try {
            $searchTerm = $request->input('q');

            if (!$searchTerm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search term is required.'
                ], 400);
            }

            $roles = DB::table('roles')
                ->where('name', 'like', '%' . $searchTerm . '%')
                ->orWhere('display_name', 'like', '%' . $searchTerm . '%')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Search completed successfully.',
                'data' => $roles
            ]);
        } catch (Exception $e) {
            Log::error('Error searching roles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while searching for roles.'
            ], 500);
        }
    }

    /**
     * API: Membuat role baru
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'badge_color' => 'required|string|in:primary,secondary,success,danger,warning,info,light,dark',
            ]);

            $roleId = DB::table('roles')->insertGetId([
                'name' => $validatedData['name'],
                'display_name' => $validatedData['display_name'],
                'description' => $validatedData['description'] ?? null,
                'badge_color' => $validatedData['badge_color'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $newRole = DB::table('roles')->find($roleId);

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully.',
                'data' => $newRole
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating role: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while creating role.'
            ], 500);
        }
    }

    /**
     * API: Update role
     */
    public function update(Request $request, $id)
    {
        try {
            $role = DB::table('roles')->where('id', $id)->first();

            if (!$role) {
                return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
            }

            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $id,
                'display_name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|nullable|string',
                'badge_color' => 'sometimes|required|string|in:primary,secondary,success,danger,warning,info,light,dark',
                'is_active' => 'sometimes|boolean',
            ]);

            if (empty($validatedData)) {
                return response()->json(['success' => false, 'message' => 'No data provided to update.'], 400);
            }

            $validatedData['updated_at'] = now();
            DB::table('roles')->where('id', $id)->update($validatedData);

            $updatedRole = DB::table('roles')->find($id);

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully.',
                'data' => $updatedRole
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error updating role ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred while updating role.'], 500);
        }
    }

    /**
     * API: Delete role
     */
    public function destroy($id)
    {
        try {
            if (!DB::table('roles')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
            }

            // Check if role is being used
            if (DB::table('user_roles')->where('role_id', $id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role. It is currently assigned to users.'
                ], 409);
            }

            DB::beginTransaction();

            // Delete role permissions
            DB::table('role_menu_permissions')->where('role_id', $id)->delete();
            DB::table('role_menus')->where('role_id', $id)->delete();

            // Delete role
            DB::table('roles')->where('id', $id)->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Role deleted successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting role ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred while deleting role.'], 500);
        }
    }
}
