<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubjectController extends Controller
{
    /**
     * Menampilkan halaman manajemen Mata Pelajaran.
     */
    public function index()
    {
        return view('subject.index');
    }

    /**
     * API: Mengambil semua data Mata Pelajaran.
     */
    public function fetchAll()
    {
        try {
            // PERBARUIAN: Menambahkan kolom 'code'
            $subjects = DB::table('subjects')
                ->select('id', 'name', 'code', 'description')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data Mata Pelajaran berhasil diambil.',
                'data' => $subjects
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching subjects: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data.',
                'data' => []
            ], 500);
        }
    }

    /**
     * API: Menyimpan data Mata Pelajaran baru.
     */
    public function store(Request $request)
    {
        try {
            // PERBARUIAN: Menambahkan validasi untuk 'code'
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:subjects,name',
                'code' => 'required|string|max:255|unique:subjects,code',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            DB::beginTransaction();

            // PERBARUIAN: Menambahkan 'code' ke data yang disimpan
            DB::table('subjects')->insert([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mata Pelajaran berhasil dibuat.',
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating subject: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.'
            ], 500);
        }
    }

    /**
     * API: Menampilkan detail satu Mata Pelajaran.
     */
    public function show($id)
    {
        try {
            $subject = DB::table('subjects')->find($id);

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diambil.',
                'data' => $subject
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching subject ID ' . $id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data.'
            ], 500);
        }
    }

    /**
     * API: Memperbarui data Mata Pelajaran.
     */
    public function update(Request $request, $id)
    {
        try {
            if (!DB::table('subjects')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            // PERBARUIAN: Menambahkan validasi untuk 'code' saat update
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:subjects,name,' . $id,
                'code' => 'required|string|max:255|unique:subjects,code,' . $id,
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            DB::beginTransaction();

            // PERBARUIAN: Menambahkan 'code' ke data yang diperbarui
            DB::table('subjects')->where('id', $id)->update([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Mata Pelajaran berhasil diperbarui.']);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating subject ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
        }
    }

    /**
     * API: Menghapus data Mata Pelajaran.
     */
    public function destroy($id)
    {
        try {
            if (!DB::table('subjects')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            DB::beginTransaction();
            DB::table('subjects')->where('id', $id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Mata Pelajaran berhasil dihapus.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting subject ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $term = $request->input('term', ''); // Ambil istilah pencarian

            $query = DB::table('subjects')->select('id', 'name');

            // Jika ada istilah pencarian, filter berdasarkan 'name' atau 'code'
            if (!empty($term)) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'LIKE', '%' . $term . '%')
                        ->orWhere('code', 'LIKE', '%' . $term . '%');
                });
            }

            // Batasi hasil agar dropdown tidak terlalu panjang
            $subjects = $query->limit(50)->get();

            // Kembalikan format yang diharapkan Select2 (data: [{id: 1, text: 'Nama'}])
            // Kita akan ubah format 'text' di JS, jadi kirim 'name' saja
            return response()->json([
                'success' => true,
                'data' => $subjects
            ]);

        } catch (Exception $e) {
            Log::error('Error searching subjects: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari data.',
                'data' => []
            ], 500);
        }
    }

    public function fetchAllAssignments()
    {
        try {
            $assignments = DB::table('subjects_assignment as sa')
                ->join('users as u', 'sa.user_id', '=', 'u.id')
                ->join('subjects as s', 'sa.subject_id', '=', 's.id')
                ->select(
                    'sa.id',
                    'u.name as teacher_name',      // Ambil nama guru
                    's.name as subject_name',    // Ambil nama mapel
                    's.code as subject_code'     // Ambil kode mapel
                )
                ->orderBy('sa.created_at', 'desc')
                ->get();

            $data = $assignments->map(function ($item) {
                return [
                    'id' => $item->id,
                    'teacher' => [
                        'name' => $item->teacher_name,
                    ],
                    'subject' => [
                        'name' => $item->subject_name,
                        'code' => $item->subject_code,
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Data Penugasan berhasil diambil.',
                'data' => $data // Kirim data yang sudah ditransformasi
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching assignments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data penugasan.' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * API: Menyimpan data Penugasan Guru baru.
     * (POST /api/subjects-assignments)
     */
    public function storeAssignment(Request $request)
    {
        try {
            // Validasi input dari form
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'subject_id' => 'required|integer|exists:subjects,id',
            ], [
                'user_id.required' => 'Guru wajib dipilih.',
                'user_id.exists' => 'Guru tidak valid.',
                'subject_id.required' => 'Mata pelajaran wajib dipilih.',
                'subject_id.exists' => 'Mata pelajaran tidak valid.',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            DB::beginTransaction();

            // Cek duplikat: Apakah guru ini sudah ditugaskan mapel yang sama?
            $exists = DB::table('subjects_assignment')
                ->where('user_id', $request->user_id)
                ->where('subject_id', $request->subject_id)
                ->exists();

            if ($exists) {
                DB::rollBack();
                // Kembalikan error 409 (Conflict) atau 422, JS Anda akan menanganinya
                return response()->json([
                    'success' => false,
                    'message' => 'Guru ini sudah ditugaskan untuk mata pelajaran tersebut.',
                ], 422);
            }

            // Simpan data baru
            DB::table('subjects_assignment')->insert([
                'user_id' => $request->user_id,
                'subject_id' => $request->subject_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Guru berhasil ditugaskan.',
            ], 201); // 201 Created

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors() // Kirim error validasi ke JS
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating subject assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.'
            ], 500);
        }
    }

    /**
     * API: Menghapus data Penugasan Guru.
     * (DELETE /api/subjects-assignments/{id})
     */
    public function destroyAssignment($id)
    {
        try {
            // Cari data penugasan
            $assignment = DB::table('subjects_assignment')->where('id', $id);

            if (!$assignment->exists()) {
                return response()->json(['success' => false, 'message' => 'Data penugasan tidak ditemukan.'], 404);
            }

            // Hapus data
            DB::beginTransaction();
            $assignment->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Penugasan berhasil dihapus.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting assignment ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }

    // ========================================================================
    // ⭐ METHOD BARU UNTUK JADWAL KELAS
    // ========================================================================

    /**
     * API: Mencari Mata Pelajaran yang SUDAH ditugaskan ke guru (untuk Select2)
     * (GET /api/subjects/assigned/search)
     */
    public function searchAssignedSubjects(Request $request)
    {
        try {
            $term = $request->input('term', '');

            $query = DB::table('subjects_assignment as sa')
                ->join('subjects as s', 'sa.subject_id', '=', 's.id')
                ->select('s.id', 's.name')
                ->distinct(); // <-- Kunci penting: 1 mapel 1 kali

            if (!empty($term)) {
                $query->where(function ($q) use ($term) {
                    $q->where('s.name', 'LIKE', '%' . $term . '%')
                        ->orWhere('s.code', 'LIKE', '%' . $term . '%');
                });
            }

            $subjects = $query->limit(50)->get();

            return response()->json([
                'success' => true,
                'data' => $subjects // Format sudah (id, name), Select2 akan jadi (id, text) di JS
            ]);

        } catch (Exception $e) {
            Log::error('Error searching assigned subjects: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari data.',
                'data' => []
            ], 500);
        }
    }

    /**
     * API: Mencari Guru yang mengajar Mata Pelajaran tertentu (untuk Select2 dependen)
     * (GET /api/subjects/{subject_id}/teachers/search)
     */
    public function searchTeachersForSubject(Request $request, $subject_id)
    {
        try {
            // Validasi sederhana
            if (!is_numeric($subject_id)) {
                return response()->json(['success' => false, 'message' => 'ID Mata Pelajaran tidak valid.', 'data' => []], 400);
            }

            $term = $request->input('term', '');

            $query = DB::table('subjects_assignment as sa')
                ->join('users as u', 'sa.user_id', '=', 'u.id')
                // --- [PERUBAHAN 1] ---
                // Tambahkan JOIN ke user_details (asumsi foreign key: user_id)
                ->join('user_details as ud', 'u.id', '=', 'ud.user_id')
                ->where('sa.subject_id', $subject_id) // <-- Filter berdasarkan mapel
                // --- [PERUBAHAN 2] ---
                // Ambil identity_number dari tabel 'ud' (user_details)
                ->select('u.id', 'u.name', 'ud.identity_number')
                ->distinct();

            if (!empty($term)) {
                $query->where(function ($q) use ($term) {
                    $q->where('u.name', 'LIKE', '%' . $term . '%')
                        // --- [PERUBAHAN 3] ---
                        // Cari identity_number di tabel 'ud'
                        ->orWhere('ud.identity_number', 'LIKE', '%' . $term . '%');
                });
            }

            $teachers = $query->limit(50)->get();

            return response()->json([
                'success' => true,
                'data' => $teachers // Format (id, name, identity_number)
            ]);

        } catch (Exception $e) {
            Log::error('Error searching teachers for subject ' . $subject_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                // Sebaiknya jangan tampilkan $e->getMessage() ke frontend
                'message' => 'Gagal mencari data guru.',
                'data' => []
            ], 500);
        }
    }
}

