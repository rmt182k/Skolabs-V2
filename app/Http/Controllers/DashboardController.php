<?php

namespace App\Http\Controllers;

use Auth;
use DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::user()->id;

        $roleData = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_id', $userId)
            ->select('roles.id', 'roles.name')
            ->first();

        $roleName = $roleData->name ?? null;
        $roleId = $roleData->id ?? null;

        $classId = null;

        if ($roleName === 'student') {
            $classId = DB::table('class_enrollments')
                ->where('student_id', $userId)
                ->value('class_id');
        }

        $userCredentials = [
            'id'       => $userId,
            'name'     => Auth::user()->name,
            'email'    => Auth::user()->email,
            'role_id'  => $roleId,
            'role'     => $roleName,
            'class_id' => $classId,
        ];

        return view('dashboard.index', compact('userCredentials'));
    }

    public function getUserCredentials()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
            }

            $userId = $user->id;

            // 1. Ambil Role (Object: id, name)
            $roleData = DB::table('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('user_id', $userId)
                ->select('roles.id', 'roles.name')
                ->first();

            // Siapkan struktur default untuk Role
            $roleObject = null;
            if ($roleData) {
                $roleObject = [
                    'id'   => $roleData->id,
                    'name' => $roleData->name,
                ];
            }

            // 2. Ambil Class (Object: id, name) - Hanya jika role student
            $classObject = null;

            // Cek apakah user punya role dan rolenya adalah 'student'
            if ($roleData && $roleData->name === 'student') {
                $classData = DB::table('class_enrollments')
                    ->join('classes', 'class_enrollments.class_id', '=', 'classes.id') // Join ke tabel master kelas
                    ->where('class_enrollments.student_id', $userId)
                    ->select('classes.id', 'classes.name') // Ambil ID dan Nama Kelas
                    ->first();

                if ($classData) {
                    $classObject = [
                        'id'   => $classData->id,
                        'name' => $classData->name,
                    ];
                }
            }

            // 3. Susun data credentials dengan struktur rapi
            $userCredentials = [
                'id'    => $userId,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $roleObject,  // Hasilnya: { "id": 1, "name": "admin" } atau null
                'class' => $classObject, // Hasilnya: { "id": 5, "name": "XII-RPL" } atau null
            ];

            return response()->json([
                'success' => true,
                'data'    => $userCredentials
            ]);
        } catch (Exception $e) {
            Log::error("Error fetching user credentials: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user credentials.'
            ], 500);
        }
    }
}
