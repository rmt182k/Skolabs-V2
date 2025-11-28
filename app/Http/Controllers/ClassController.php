<?php

namespace App\Http\Controllers;

use Auth;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Log;
use Validator;

class ClassController extends Controller
{
    /**
     * Menampilkan halaman manajemen Kelas.
     */
    public function index()
    {
        return view('class.index');
    }

    /**
     * API: Mengambil semua data Kelas dengan relasinya.
     */
    public function fetchAll()
    {
        try {
            // Kita pastikan query ini reusable
            $classes = $this->getBaseClassQuery()->get();

            return response()->json([
                'success' => true,
                'message' => 'Semua Data Kelas berhasil diambil (Admin View).',
                'data' => $classes
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching all classes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data.',
                'data' => []
            ], 500);
        }
    }

    public function fetchUserClasses(Request $request)
    {
        try {
            $user = Auth::user();
            $userId = $user->id;

            // Ambil nama role user dari tabel roles melalui user_roles
            // Asumsi kamu menggunakan query builder manual, tapi jika pakai Spatie/Laratrust bisa lebih mudah ($user->hasRole('...'))
            // Di sini saya pakai query manual sesuai style kodemu:
            $roles = DB::table('user_roles as ur')
                ->join('roles as r', 'ur.role_id', '=', 'r.id')
                ->where('ur.user_id', $userId)
                ->pluck('r.name')
                ->toArray();

            $query = $this->getBaseClassQuery();

            // LOGIKA PENGECEKAN ROLE
            if (in_array('super_admin', $roles) || in_array('admin', $roles)) {
                // ===============================================
                // CASE 1: ADMIN / SUPER ADMIN
                // ===============================================
                // Tidak perlu filter tambahan, ambil semua.
                $message = 'Data Seluruh Kelas (Admin).';

            } elseif (in_array('teacher', $roles) || in_array('guru', $roles)) {
                // ===============================================
                // CASE 2: GURU (TEACHER)
                // ===============================================
                // Logika: Cek class_schedules dimana user_id = guru ini
                // Kita gunakan DISTINCT karena satu guru bisa mengajar user mapel berbeda di kelas yang sama (hari beda)
                // agar kelas tidak muncul double.

                $query->join('class_schedules as cs', 'c.id', '=', 'cs.class_id')
                    ->where('cs.user_id', $userId)
                    ->select('c.*', 'el.name as educational_level_name', 'm.name as major_name', 'ay.name as academic_year_name') // Re-select untuk menghindari ambiguitas kolom id setelah join
                    ->distinct();

                $message = 'Data Kelas Ajar Anda.';

            } elseif (in_array('student', $roles) || in_array('murid', $roles)) {
                // ===============================================
                // CASE 3: MURID (STUDENT)
                // ===============================================
                // Logika: Cek class_enrollments dimana student_id = murid ini

                $query->join('class_enrollments as ce', 'c.id', '=', 'ce.class_id')
                    ->where('ce.student_id', $userId);

                $message = 'Data Kelas Anda.';

            } else {
                // Jika role tidak dikenali
                return response()->json([
                    'success' => false,
                    'message' => 'Role tidak memiliki akses ke data kelas.',
                    'data' => []
                ], 403);
            }

            $classes = $query->orderBy('c.created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => $message,
                'role_detected' => $roles, // Debugging info (opsional)
                'data' => $classes
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching user classes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    private function getBaseClassQuery()
    {
        return DB::table('classes as c')
            ->leftJoin('educational_levels as el', 'c.educational_level_id', '=', 'el.id')
            ->leftJoin('majors as m', 'c.major_id', '=', 'm.id')
            ->leftJoin('academic_years as ay', 'c.academic_year_id', '=', 'ay.id')
            ->select(
                'c.id',
                'c.name',
                'c.grade_level',
                'c.suffix',
                'el.name as educational_level_name',
                'm.name as major_name',
                'ay.name as academic_year_name',
                'c.educational_level_id',
                'c.major_id',
                'c.academic_year_id'
            );
    }

    /**
     * API: Menyimpan data Kelas baru.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'grade_level' => 'required|integer|min:1',
                'educational_level_id' => 'required|exists:educational_levels,id',
                'academic_year_id' => 'required|exists:academic_years,id',
                'major_id' => 'nullable|exists:majors,id',
                // === PERUBAHAN DI SINI ===
                // 1. Ganti 'class_index' menjadi 'suffix'
                // 2. Sesuaikan dengan skema (nullable, string)
                'suffix' => 'nullable|string|max:10', // Sesuai skema: nullable
                // === SELESAI PERUBAHAN ===
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            DB::beginTransaction();

            // === PERUBAHAN DI SINI: Logika Generate Name ===
            // 1. Dapatkan major code (jika ada)
            $majorCode = $request->major_id ? DB::table('majors')->where('id', $request->major_id)->value('code') : null;

            // 2. Bangun bagian-bagian nama
            $nameParts = [
                $request->grade_level,
                $majorCode, // Akan bernilai null jika tidak ada major_id
                $request->suffix ? strtoupper($request->suffix) : null // Ambil suffix jika ada
            ];

            // 3. Gabungkan bagian yang ada (filter null/kosong) dengan spasi
            $generatedName = implode(' ', array_filter($nameParts));
            // === SELESAI PERUBAHAN ===

            // Pastikan nama unik untuk tahun ajaran yang sama
            $isNameExists = DB::table('classes')
                ->where('name', $generatedName)
                ->where('academic_year_id', $request->academic_year_id)
                ->exists();

            if ($isNameExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    // Arahkan error ke field 'suffix' (atau 'grade_level' jika lebih sesuai)
                    'errors' => ['suffix' => ['Kombinasi kelas dengan nama "' . $generatedName . '" sudah ada untuk tahun ajaran ini.']]
                ], 422);
            }

            DB::table('classes')->insert([
                'name' => $generatedName,
                'grade_level' => $request->grade_level,
                'suffix' => $request->suffix, // <-- SIMPAN SUFFIX
                'educational_level_id' => $request->educational_level_id,
                'academic_year_id' => $request->academic_year_id,
                'major_id' => $request->major_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Kelas berhasil dibuat.'], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating class: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Menampilkan detail satu Kelas.
     */
    public function show($id)
    {
        try {
            // 'find' akan mengambil semua kolom, termasuk 'suffix'
            $class = DB::table('classes')->find($id);

            if (!$class) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            // === PERUBAHAN DI SINI ===
            // Logika parsing 'name' tidak diperlukan lagi.
            // Data 'suffix' sudah ada di $class->suffix
            // Kita hanya perlu memastikan JS di frontend membaca 'suffix', bukan 'class_index'
            // (Ini sudah ditangani di file class.js yang baru)
            // === SELESAI PERUBAHAN ===

            return response()->json(['success' => true, 'message' => 'Data berhasil diambil.', 'data' => $class]);

        } catch (Exception $e) {
            Log::error('Error fetching class ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat mengambil data.'], 500);
        }
    }

    /**
     * API: Memperbarui data Kelas.
     */
    public function update(Request $request, $id)
    {
        try {
            if (!DB::table('classes')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            $validator = Validator::make($request->all(), [
                'grade_level' => 'required|integer|min:1',
                'educational_level_id' => 'required|exists:educational_levels,id',
                'academic_year_id' => 'required|exists:academic_years,id',
                'major_id' => 'nullable|exists:majors,id',
                // === PERUBAHAN DI SINI ===
                'suffix' => 'nullable|string|max:10', // Sesuai skema
                // === SELESAI PERUBAHAN ===
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            DB::beginTransaction();

            // === PERUBAHAN DI SINI: Logika Generate Name (sama seperti store) ===
            $majorCode = $request->major_id ? DB::table('majors')->where('id', $request->major_id)->value('code') : null;
            $nameParts = [
                $request->grade_level,
                $majorCode,
                $request->suffix ? strtoupper($request->suffix) : null
            ];
            $generatedName = implode(' ', array_filter($nameParts));
            // === SELESAI PERUBAHAN ===

            // Pastikan nama unik (kecuali untuk record itu sendiri) pada tahun ajaran yang sama
            $isNameExists = DB::table('classes')
                ->where('name', $generatedName)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('id', '!=', $id)
                ->exists();

            if ($isNameExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors' => ['suffix' => ['Kombinasi kelas dengan nama "' . $generatedName . '" sudah ada untuk tahun ajaran ini.']]
                ], 422);
            }


            DB::table('classes')->where('id', $id)->update([
                'name' => $generatedName,
                'grade_level' => $request->grade_level,
                'suffix' => $request->suffix, // <-- SIMPAN SUFFIX
                'educational_level_id' => $request->educational_level_id,
                'academic_year_id' => $request->academic_year_id,
                'major_id' => $request->major_id,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Kelas berhasil diperbarui.']);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating class ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
        }
    }

    /**
     * API: Menghapus data Kelas.
     */
    public function destroy($id)
    {
        try {
            if (!DB::table('classes')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            // Tambahkan validasi jika kelas sudah digunakan (opsional)
            // Cek tabel 'student_classes' atau tabel lain yang berelasi
            if (DB::table('student_classes')->where('class_id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus, kelas ini memiliki siswa.'], 400);
            }

            DB::beginTransaction();
            DB::table('classes')->where('id', $id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Kelas berhasil dihapus.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting class ID ' . $id . ': ' . $e->getMessage());
            // Tangani error foreign key
            if ($e->getCode() == 23000) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus. Kelas ini mungkin sudah digunakan di data lain (misal: data siswa).'], 500);
            }
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }
}
