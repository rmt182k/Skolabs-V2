<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    public function index()
    {
        return view('permission.index');
    }

    /**
     * API: Mengambil semua permissions tanpa grouping
     */
    public function fetchPermissions()
    {
        try {
            $permissions = DB::table('permissions')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Permissions retrieved successfully.',
                'data' => $permissions
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching permissions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching permissions.'
            ], 500);
        }
    }

    /**
     * API: Mengambil satu permission berdasarkan ID
     */
    public function show($id)
    {
        try {
            $permission = DB::table('permissions')->find($id);

            if (!$permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Permission retrieved successfully.',
                'data' => $permission
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching permission ID ' . $id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching the permission.'
            ], 500);
        }
    }

    /**
     * API: Membuat permission baru
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $permissionId = DB::table('permissions')->insertGetId([
                'name' => $validatedData['name'],
                'display_name' => $validatedData['display_name'],
                'description' => $validatedData['description'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $newPermission = DB::table('permissions')->find($permissionId);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully.',
                'data' => $newPermission
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while creating permission.'
            ], 500);
        }
    }

    /**
     * API: Update permission
     */
    public function update(Request $request, $id)
    {
        try {
            $permission = DB::table('permissions')->where('id', $id)->first();

            if (!$permission) {
                return response()->json(['success' => false, 'message' => 'Permission not found.'], 404);
            }

            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:permissions,name,' . $id,
                'display_name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|nullable|string',
            ]);

            if (empty($validatedData)) {
                return response()->json(['success' => false, 'message' => 'No data provided to update.'], 400);
            }

            $validatedData['updated_at'] = now();
            DB::table('permissions')->where('id', $id)->update($validatedData);

            $updatedPermission = DB::table('permissions')->find($id);

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully.',
                'data' => $updatedPermission
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error updating permission ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred while updating permission.'], 500);
        }
    }

    /**
     * API: Delete permission
     */
    public function destroy($id)
    {
        try {
            if (!DB::table('permissions')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Permission not found.'], 404);
            }

            // Check if permission is being used
            if (
                DB::table('role_menu_permissions')->where('permission_id', $id)->exists() ||
                DB::table('user_menu_permission_overrides')->where('permission_id', $id)->exists()
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete permission. It is currently in use.'
                ], 409);
            }

            DB::table('permissions')->where('id', $id)->delete();

            return response()->json(['success' => true, 'message' => 'Permission deleted successfully.']);
        } catch (Exception $e) {
            Log::error('Error deleting permission ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred while deleting permission.'], 500);
        }
    }
}
