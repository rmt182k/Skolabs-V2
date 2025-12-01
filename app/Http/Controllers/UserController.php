<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Menampilkan halaman user management
     */
    public function index()
    {
        // Seharusnya mengarah ke view yang sudah kita buat
        return view('user.index');
    }

    /**
     * API: Mengambil semua users dengan roles dan permissions
     */
    public function fetchUsers(Request $request)
    {
        try {
            $query = DB::table('users')
                // 1. DITAMBAHKAN: leftJoin ke user_details
                ->leftJoin('user_details', 'users.id', '=', 'user_details.user_id')
                // 2. DIPERBARUI: Memilih kolom secara spesifik untuk menghindari konflik
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.is_active',
                    'users.email_verified_at', // <-- Ditambahkan untuk render 'Verified'/'Not Verified'
                    'users.created_at',
                    'user_details.identity_number',
                    'user_details.gender',
                    'user_details.phone_number',
                    'user_details.avatar'
                )
                ->orderBy('users.created_at', 'desc');

            // Filter by role (Logika ini tetap)
            if ($request->has('role') && $request->role != '') {
                $query->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                    ->where('user_roles.role_id', $request->role);
            }

            // Filter by status (Logika ini tetap)
            if ($request->has('status') && $request->status != '') {
                $isActive = $request->status === 'active' ? 1 : 0;
                $query->where('users.is_active', $isActive);
            }

            // Search (Logika ini tetap)
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        // 3. DITAMBAHKAN: Pencarian ke kolom di user_details
                        ->orWhere('user_details.identity_number', 'like', "%{$search}%")
                        ->orWhere('user_details.phone_number', 'like', "%{$search}%");
                });
            }

            // 4. DIPERBARUI: distinct() sekarang berdasarkan users.id yang sudah di-select
            // Ini penting jika filter 'role' aktif dan user punya > 1 role
            $users = $query->distinct('users.id')->get();

            // Ambil roles untuk setiap user (N+1 query, tapi ini dari kode asli Anda)
            foreach ($users as $user) {
                $user->roles = DB::table('user_roles')
                    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                    ->where('user_roles.user_id', $user->id)
                    ->select('roles.*') // <-- Ambil semua data role
                    ->get();
            }

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully.',
                'data' => $users
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching users.'
            ], 500);
        }
    }

    /**
     * API: Mengambil detail satu user berdasarkan ID
     */
    public function show($id)
    {
        try {
            // Join dengan user_details
            $user = DB::table('users')
                ->leftJoin('user_details', 'users.id', '=', 'user_details.user_id')
                ->where('users.id', $id)
                ->select(
                    'users.*',
                    'user_details.identity_number',
                    'user_details.date_of_birth',
                    'user_details.gender',
                    'user_details.phone_number',
                    'user_details.address',
                    'user_details.avatar' // Avatar juga diambil jika ada
                )
                ->first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 404);
            }

            // Ambil roles untuk user ini
            $user->roles = DB::table('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('user_roles.user_id', $user->id)
                ->select('roles.id', 'roles.display_name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully.',
                'data' => $user
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching user ID ' . $id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching user details.'
            ], 500);
        }
    }

    /**
     * API: Membuat user baru
     */
    public function store(Request $request)
    {
        try {
            // Validasi gabungan untuk users dan user_details
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'password' => 'required|string|min:6|confirmed',
                'roles' => 'sometimes|array',
                'roles.*' => 'exists:roles,id',

                // Validasi untuk user_details (semua nullable)
                'identity_number' => 'nullable|string|max:255|unique:user_details,identity_number',
                'date_of_birth' => 'nullable|date_format:Y-m-d',
                'gender' => 'nullable|in:male,female',
                'phone_number' => 'nullable|string|max:20',
                'address' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // 1. Simpan ke tabel 'users'
            $userData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'is_active' => true, // Default aktif saat dibuat
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($request->has('verify_now') && $request->input('verify_now') === 'on') {
                $userData['email_verified_at'] = now();
            }

            $userId = DB::table('users')->insertGetId($userData);

            // 2. Simpan ke tabel 'user_roles'
            if (!empty($validatedData['roles'])) {
                $userRolesData = [];
                foreach ($validatedData['roles'] as $roleId) {
                    $userRolesData[] = [
                        'user_id' => $userId,
                        'role_id' => $roleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('user_roles')->insert($userRolesData);
            }

            $this->updateUserDetails($request, $userId);

            DB::commit();

            $newUser = (object) array_merge($userData, ['id' => $userId]);
            unset($newUser->password);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'data' => $newUser
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.'
            ], 500);
        }
    }

    /**
     * API: Update user
     */
    public function update(Request $request, $id)
    {
        try {
            if (!DB::table('users')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 404);
            }

            // Validasi gabungan untuk update
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|max:255|unique:users,email,' . $id,
                'password' => 'sometimes|nullable|string|min:6|confirmed', // Tambahkan 'confirmed'
                'is_active' => 'sometimes|boolean',
                'roles' => 'sometimes|array',
                'roles.*' => 'exists:roles,id',

                // Validasi untuk user_details
                'identity_number' => ['nullable', 'string', 'max:255', Rule::unique('user_details')->ignore($id, 'user_id')],
                'date_of_birth' => 'nullable|date_format:Y-m-d',
                'gender' => 'nullable|in:male,female',
                'phone_number' => 'nullable|string|max:20',
                'address' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // 1. Update tabel 'users'
            $updateData = [];
            if ($request->has('name'))
                $updateData['name'] = $validatedData['name'];
            if ($request->has('email'))
                $updateData['email'] = $validatedData['email'];
            if ($request->has('is_active'))
                $updateData['is_active'] = $validatedData['is_active'];

            // Hanya update password jika diisi
            if (!empty($validatedData['password'])) {
                $updateData['password'] = Hash::make($validatedData['password']);
            }

            if (!empty($updateData)) {
                $updateData['updated_at'] = now();
                DB::table('users')->where('id', $id)->update($updateData);
            }

            // 2. Update tabel 'user_roles' (Sinkronisasi)
            // Hanya update role jika field 'roles' dikirimkan
            if ($request->has('roles')) {
                $roleIds = $validatedData['roles'] ?? []; // Jika 'roles' ada tapi kosong, $roleIds = []
                DB::table('user_roles')->where('user_id', $id)->delete(); // Hapus role lama

                if (!empty($roleIds)) {
                    $newRoles = [];
                    foreach ($roleIds as $roleId) {
                        $newRoles[] = [
                            'user_id' => $id,
                            'role_id' => $roleId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    DB::table('user_roles')->insert($newRoles);
                }
            }

            $this->updateUserDetails($request, $id);

            DB::commit();

            $updatedUser = DB::table('users')->find($id);
            return response()->json(['success' => true, 'message' => 'User and details updated successfully.', 'data' => $updatedUser]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating user ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Untuk create/update data di table user_details
     */
    private function updateUserDetails(Request $request, $userId)
    {
        // Ambil hanya field yang relevan untuk user_details
        $detailFields = [
            'identity_number' => $request->input('identity_number'),
            'date_of_birth' => $request->input('date_of_birth'),
            'gender' => $request->input('gender'),
            'phone_number' => $request->input('phone_number'),
            'address' => $request->input('address'),
        ];

        // Bersihkan data: ubah string kosong "" menjadi null
        // Ini penting agar 'nullable' di database berfungsi
        $cleanedDetails = array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $detailFields);

        // Cek apakah semua field yang dikirim memang null
        $allNull = true;
        foreach ($cleanedDetails as $value) {
            if ($value !== null) {
                $allNull = false;
                break;
            }
        }

        // Cek apakah data user_details sudah ada
        $detailsExist = DB::table('user_details')->where('user_id', $userId)->exists();

        // Jika semua field null DAN data belum ada, jangan lakukan apa-apa
        if ($allNull && !$detailsExist) {
            return;
        }

        // Gunakan updateOrInsert
        // 1. Kriteria pencarian: ['user_id' => $userId]
        // 2. Data untuk di-insert/update
        DB::table('user_details')->updateOrInsert(
            ['user_id' => $userId],
            $cleanedDetails + ['updated_at' => now()]
            // `created_at` akan otomatis ditangani oleh updateOrInsert jika record baru
        );
    }

    /**
     * API: Delete user
     */
    public function destroy($id)
    {
        try {
            if (!DB::table('users')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 404);
            }

            DB::beginTransaction();
            // Hapus relasi
            DB::table('user_roles')->where('user_id', $id)->delete();
            // Hapus detail (jika ada)
            DB::table('user_details')->where('user_id', $id)->delete();
            // Hapus user utama
            DB::table('users')->where('id', $id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting user ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
        }
    }

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
     * API: Memberikan atau mengubah roles untuk seorang user.
     */
    public function assignRoles(Request $request, $userId)
    {
        try {
            $validatedData = $request->validate([
                'roles' => 'sometimes|array',
                'roles.*' => 'exists:roles,id'
            ]);

            if (!DB::table('users')->where('id', $userId)->exists()) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 404);
            }

            $roleIds = $validatedData['roles'] ?? [];

            DB::beginTransaction();

            // Hapus semua role lama dari user ini
            DB::table('user_roles')->where('user_id', $userId)->delete();

            // Tambahkan role yang baru
            if (!empty($roleIds)) {
                $newRoles = [];
                foreach ($roleIds as $roleId) {
                    $newRoles[] = [
                        'user_id' => $userId,
                        'role_id' => $roleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('user_roles')->insert($newRoles);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Roles updated successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error assigning roles to user $userId: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update roles.'], 500);
        }
    }

    // ==================================================================
    // PERBAIKAN DI BAWAH INI
    // ==================================================================

    /**
     * API: Mengambil semua user yang memiliki role tertentu.
     */
    public function getUsersByRole(Request $request, $roleId)
    {
        try {
            $query = DB::table('users')
                ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                ->leftJoin('user_details', 'users.id', '=', 'user_details.user_id')
                ->where('user_roles.role_id', $roleId)
                // ->where('users.is_active', 1) // <-- INI DIHAPUS. Biarkan DataTables menampilkan semua status
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.is_active', // <-- DITAMBAHKAN (Penting untuk status toggle)
                    'users.email_verified_at', // <-- DITAMBAHKAN (Penting untuk info user)
                    'users.created_at',
                    'user_details.identity_number',
                    'user_details.gender',
                    'user_details.phone_number',
                    'user_details.avatar'
                );

            // Logika 'term' dan 'exclude_class_id' dari kode asli Anda
            // sepertinya untuk Select2, bukan untuk DataTables.
            // Untuk DataTables, kita biarkan query-nya seperti ini,
            // tapi kita tambahkan distinct() untuk menghindari duplikat jika
            // user punya > 1 role (meskipun di sini tidak relevan karena sudah difilter by roleId)

            // Filter pencarian (term) dari Select2
            // Kita cek BUKAN DARI DATATABLES
            if ($request->has('term') && $request->input('term') != '') {
                $term = $request->input('term');
                $query->where(function ($q) use ($term) {
                    $q->where('users.name', 'like', "%{$term}%")
                        ->orWhere('user_details.identity_number', 'like', "%{$term}%");
                });
            }

            // Filter pengecualian (exclude_class_id)
            if ($request->has('exclude_class_id') && $request->input('exclude_class_id') != '') {
                $excludeClassId = $request->input('exclude_class_id');

                // 1. Cari tahu Tahun Ajaran dari kelas yang sedang dibuka (Class 1B)
                $classAcademicYearId = DB::table('classes')
                    ->where('id', $excludeClassId)
                    ->value('academic_year_id');

                if ($classAcademicYearId) {
                    // 2. LOGIKA BARU:
                    // Ambil SEMUA student_id yang sudah terdaftar di kelas MANAPUN
                    // pada tahun ajaran tersebut.
                    $studentsWithClass = DB::table('class_enrollments')
                        ->where('academic_year_id', $classAcademicYearId) // <--- Kuncinya disini
                        ->pluck('student_id');

                    // 3. Keluarkan mereka dari list pencarian
                    // Jadi siswa kelas 1A tidak akan muncul saat cari siswa untuk 1B
                    $query->whereNotIn('users.id', $studentsWithClass);
                }
            }

            // Eksekusi query
            // Jika ini untuk Select2, tambahkan limit. Jika untuk Datatables, tidak perlu.
            // Kita asumsikan ini BISA jadi Select2, jadi kita tambahkan limit
            // TAPI, DataTables juga memanggil ini. Kita perlu membedakan.

            // Untuk SEKARANG, kita buat agar FOKUS ke DataTables.
            // Logika Select2 (term, exclude) kita abaikan sementara jika ini murni untuk DataTables
            // Mari kita buat query yang bersih HANYA untuk DataTables

            $queryForDataTables = DB::table('users')
                ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                ->leftJoin('user_details', 'users.id', '=', 'user_details.user_id')
                ->where('user_roles.role_id', $roleId)
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.is_active',
                    'users.email_verified_at',
                    'users.created_at',
                    'user_details.identity_number',
                    'user_details.gender',
                    'user_details.phone_number',
                    'user_details.avatar'
                )
                ->distinct('users.id') // <-- Tambahkan distinct
                ->orderBy('users.name', 'asc');


            // Cek apakah ini permintaan Select2?
            if ($request->has('term') || $request->has('exclude_class_id')) {
                // Ini adalah permintaan Select2, gunakan query lama Anda
                $users = $query->orderBy('users.name', 'asc')->limit(20)->get();
            } else {
                // Ini adalah permintaan DataTables
                $users = $queryForDataTables->get();
            }


            // **LANGKAH PALING PENTING YANG HILANG**
            // Kita harus menambahkan data roles ke setiap user, sama seperti di fetchUsers()
            foreach ($users as $user) {
                $user->roles = DB::table('user_roles')
                    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                    ->where('user_roles.user_id', $user->id)
                    ->select('roles.*') // <-- Ambil semua data role
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (Exception $e) {
            Log::error("Error fetching users for role $roleId: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users by role.'
            ], 500);
        }
    }


    /**
     * API: Mengubah status aktif/non-aktif seorang user.
     */
    public function updateStatus(Request $request, $userId)
    {
        try {
            $validatedData = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $affected = DB::table('users')
                ->where('id', $userId)
                ->update([
                    'is_active' => $validatedData['is_active'],
                    'updated_at' => now() // Selalu update timestamp
                ]);

            if ($affected > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'User status updated successfully.'
                ]);
            }

            if (DB::table('users')->where('id', $userId)->exists()) {
                return response()->json([
                    'success' => true, // Tetap sukses
                    'message' => 'User status is already the same.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);

        } catch (Exception $e) {
            Log::error("Error updating status for user $userId: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status.' . $e->getMessage()
            ], 500);
        }
    }
}
