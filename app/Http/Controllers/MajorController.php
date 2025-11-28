<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MajorController extends Controller
{
    /**
     * Menampilkan halaman manajemen Jurusan.
     */
    public function index()
    {
        return view('major.index');
    }

    /**
     * API: Mengambil semua data Jurusan dengan relasinya.
     */
    public function fetchAll()
    {
        try {
            $majors = DB::table('majors as m')
                ->join('educational_levels as el', 'm.educational_level_id', '=', 'el.id')
                ->select(
                    'm.id',
                    'm.name',
                    'm.code', // --- TAMBAHAN ---
                    'm.description',
                    'm.educational_level_id',
                    'el.name as educational_level_name'
                )
                ->orderBy('m.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data Jurusan berhasil diambil.',
                'data'    => $majors
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching majors: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data.',
                'data'    => []
            ], 500);
        }
    }

    /**
     * API: Menyimpan data Jurusan baru.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'educational_level_id' => 'required|exists:educational_levels,id',
                'name' => 'required|string|max:255|unique:majors,name',
                'code' => 'required|string|max:50|unique:majors,code', // --- TAMBAHAN ---
                'description' => 'required|string',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            DB::beginTransaction();

            DB::table('majors')->insert([
                'educational_level_id' => $request->educational_level_id,
                'name' => $request->name,
                'code' => $request->code, // --- TAMBAHAN ---
                'description' => $request->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Jurusan berhasil dibuat.'], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating major: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan data.'], 500);
        }
    }

    /**
     * API: Menampilkan detail satu Jurusan.
     */
    public function show($id)
    {
        try {
            // find($id) akan mengambil semua kolom, termasuk 'code'
            $major = DB::table('majors')->find($id);

            if (!$major) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Data berhasil diambil.', 'data' => $major]);
        } catch (Exception $e) {
            Log::error('Error fetching major ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat mengambil data.'], 500);
        }
    }

    /**
     * API: Memperbarui data Jurusan.
     */
    public function update(Request $request, $id)
    {
        try {
            if (!DB::table('majors')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            $validator = Validator::make($request->all(), [
                'educational_level_id' => 'required|exists:educational_levels,id',
                'name' => 'required|string|max:255|unique:majors,name,' . $id,
                'code' => 'required|string|max:50|unique:majors,code,' . $id, // --- TAMBAHAN ---
                'description' => 'required|string',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            DB::beginTransaction();

            DB::table('majors')->where('id', $id)->update([
                'educational_level_id' => $request->educational_level_id,
                'name' => $request->name,
                'code' => $request->code, // --- TAMBAHAN ---
                'description' => $request->description,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Jurusan berhasil diperbarui.']);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating major ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
        }
    }

    /**
     * API: Menghapus data Jurusan.
     */
    public function destroy($id)
    {
        try {
            if (!DB::table('majors')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            DB::beginTransaction();
            DB::table('majors')->where('id', $id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Jurusan berhasil dihapus.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting major ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }
}
