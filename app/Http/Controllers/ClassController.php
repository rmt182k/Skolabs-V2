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

    public function fetchUserClasses($userId)
    {
        try {
            // Ambil role
            $roles = DB::table('user_roles as ur')
                ->join('roles as r', 'ur.role_id', '=', 'r.id')
                ->where('ur.user_id', $userId)
                ->pluck('r.name')
                ->toArray();

            $query = $this->getBaseClassQuery();
            $isTeacherContext = false; // Flag untuk menandai apakah perlu format ulang data subject

            // LOGIKA PENGECEKAN ROLE
            if (in_array('super_admin', $roles) || in_array('admin', $roles)) {
                // CASE 1: ADMIN
                $message = 'Data Seluruh Kelas (Admin).';

            } elseif (in_array('teacher', $roles) || in_array('guru', $roles)) {
                // CASE 2: GURU (TEACHER)
                // Join ke class_schedules, lalu Join lagi ke subjects
                $query->join('class_schedules as cs', 'c.id', '=', 'cs.class_id')
                    ->join('subjects as s', 'cs.subject_id', '=', 's.id') // <--- HUBUNGKAN KE SUBJECTS
                    ->where('cs.user_id', $userId)
                    ->select(
                        'c.*',
                        'el.name as educational_level_name',
                        'm.name as major_name',
                        'ay.name as academic_year_name',
                        // Ambil data Subject juga
                        's.id as subject_id',
                        's.name as subject_name',
                        's.code as subject_code'
                    );

                // PENTING: Jangan pakai distinct() di query SQL jika ingin mengambil subject,
                // karena kita butuh data subject yang berbeda-beda itu ditarik dulu.
                // Kita akan merapikannya (distinct) menggunakan Collection di bawah.

                $isTeacherContext = true;
                $message = 'Data Kelas Ajar Anda beserta Mata Pelajaran.';

            } elseif (in_array('student', $roles) || in_array('murid', $roles)) {
                // CASE 3: MURID
                $query->join('class_enrollments as ce', 'c.id', '=', 'ce.class_id')
                    ->where('ce.student_id', $userId);

                // Tambahkan distinct agar jika murid terdaftar ganda (error data) tetap muncul satu
                $query->distinct();
                $message = 'Data Kelas Anda.';

            } else {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }

            // Eksekusi Query
            $rawResults = $query->orderBy('c.created_at', 'desc')->get();

            // LOGIKA TRANSFORMAT DATA (KHUSUS GURU)
            // Agar kelas yang sama tidak muncul 2 kali, tapi subjects-nya digabung jadi array
            if ($isTeacherContext) {
                $classes = $rawResults->groupBy('id')->map(function ($classRows) {
                    // Ambil info detail kelas dari baris pertama saja
                    $classInfo = $classRows->first();

                    // Buat property baru 'subjects' yang berisi list mapel di kelas ini
                    $classInfo->subjects = $classRows->map(function ($row) {
                        return [
                            'id' => $row->subject_id,
                            'name' => $row->subject_name,
                            'code' => $row->subject_code
                        ];
                    })->unique('id')->values(); // Pastikan subject unik & reset key array

                    // Bersihkan property temp (opsional, biar rapi response json-nya)
                    unset($classInfo->subject_id);
                    unset($classInfo->subject_name);
                    unset($classInfo->subject_code);

                    return $classInfo;
                })->values(); // Reset key array utama
            } else {
                // Untuk Admin & Student, gunakan data langsung
                $classes = $rawResults;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
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
