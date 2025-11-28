<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;
use Carbon\Carbon;

class CompetencyController extends Controller
{
    /**
     * Menampilkan halaman index (daftar) kompetensi.
     */
    public function index()
    {
        return view('competency.index');
    }

    /**
     * API: Mengambil data kompetensi untuk DataTables.
     */
    public function getCompetencies(Request $request)
    {
        try {
            // DIPERBARUI: Tambahkan JOIN ke tabel subjects
            $competencies = DB::table('competencies')
                ->join('subjects', 'competencies.subject_id', '=', 'subjects.id')
                ->select(
                    'competencies.id',
                    'competencies.name',
                    'competencies.description',
                    'subjects.name as subject_name' // Ambil nama mata pelajaran
                )
                ->get();

            return response()->json(['data' => $competencies]);
        } catch (Exception $e) {
            Log::error('Error fetching competencies: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data.'], 500);
        }
    }

    /**
     * API: Menyimpan kompetensi baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|numeric|exists:subjects,id', // BARU
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'subject_id.required' => 'Mata pelajaran wajib dipilih.' // Pesan error kustom
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $id = DB::table('competencies')->insertGetId([
                'subject_id' => $request->subject_id, // BARU
                'name' => $request->name,
                'description' => $request->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kompetensi berhasil disimpan!',
                'competency_id' => $id
            ], 201);
        } catch (Exception $e) {
            Log::error('Error storing competency: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data.'], 500);
        }
    }

    /**
     * API: Mengambil detail satu kompetensi (untuk modal edit).
     */
    public function show($id)
    {
        try {
            // DIPERBARUI: Join untuk mengambil data subject
            $competency = DB::table('competencies')
                ->join('subjects', 'competencies.subject_id', '=', 'subjects.id')
                ->where('competencies.id', $id)
                ->select(
                    'competencies.*',
                    'subjects.name as subject_name' // Kita perlu ini untuk pre-select Select2
                )
                ->first();

            if (!$competency) {
                return response()->json(['success' => false, 'message' => 'Kompetensi tidak ditemukan.'], 404);
            }

            return response()->json(['success' => true, 'data' => $competency]);
        } catch (Exception $e) {
            Log::error('Error showing competency ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data.'], 500);
        }
    }

    /**
     * API: Memperbarui kompetensi yang ada.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|numeric|exists:subjects,id', // BARU
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'subject_id.required' => 'Mata pelajaran wajib dipilih.'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $updated = DB::table('competencies')->where('id', $id)->update([
                'subject_id' => $request->subject_id, // BARU
                'name' => $request->name,
                'description' => $request->description,
                'updated_at' => now(),
            ]);

            if ($updated == 0) {
                return response()->json(['success' => false, 'message' => 'Kompetensi tidak ditemukan atau tidak ada perubahan data.'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Kompetensi berhasil diperbarui!']);
        } catch (Exception $e) {
            Log::error('Error updating competency ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui data.'], 500);
        }
    }

    /**
     * API: Menghapus kompetensi.
     */
    public function destroy($id)
    {
        // ... (Fungsi destroy Anda sudah aman dan tidak perlu diubah)
        try {
            $isUsed = DB::table('question_competency_allocation')
                ->where('competency_id', $id)
                ->exists();

            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus! Kompetensi ini sedang digunakan dalam satu atau lebih pertanyaan tugas.'
                ], 422);
            }

            DB::table('competencies')->where('id', $id)->delete();

            return response()->json(['success' => true, 'message' => 'Kompetensi berhasil dihapus.']);
        } catch (Exception $e) {
            Log::error('Error deleting competency ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data.'], 500);
        }
    }

    /**
     * API: Pencarian kompetensi untuk Select2 (dari form Tugas).
     */
    public function search(Request $request)
    {
        // ... (Fungsi ini tidak terkait langsung dengan CRUD ini, biarkan saja)
        try {
            $term = $request->query('term', '');

            $results = DB::table('competencies')
                ->where('name', 'LIKE', '%' . $term . '%')
                ->select('id', 'name')
                ->limit(20)
                ->get();

            return response()->json(['data' => $results]);
        } catch (Exception $e) {
            Log::error('Error searching competencies: ' . $e->getMessage());
            return response()->json(['data' => []], 500);
        }
    }
}
