<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
// use Yajra\DataTables\Facades\DataTables; // Dihapus karena tidak lagi digunakan

class EducationalLevelController extends Controller
{
    /**
     * Menampilkan halaman manajemen Jenjang Pendidikan.
     * Metode ini menangani route web.
     */
    public function index()
    {
        return view('educational-level.index');
    }

    /**
     * API: Mengambil semua data Jenjang Pendidikan untuk client-side DataTables.
     * Metode ini menangani route API.
     */
    public function fetchAll()
    {
        try {
            // Mengambil semua data dan membuat alias 'duration' agar kompatibel dengan JS
            $levels = DB::table('educational_levels')
                ->selectRaw('id, name, description, duration_years as duration')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data Jenjang Pendidikan berhasil diambil.',
                'data' => $levels
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching educational levels: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data.' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Menyimpan data Jenjang Pendidikan baru.
     */
    public function store(Request $request)
    {
        try {
            // Validasi input 'duration' dari form
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:educational_levels,name',
                'description' => 'nullable|string',
                'duration' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            DB::beginTransaction();

            // Memetakan 'duration' dari request ke kolom 'duration_years' di database
            $levelData = [
                'name' => $request->name,
                'description' => $request->description,
                'duration_years' => $request->duration,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('educational_levels')->insert($levelData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jenjang Pendidikan berhasil dibuat.',
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
            Log::error('Error creating educational level: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.'
            ], 500);
        }
    }

    /**
     * API: Menampilkan detail satu Jenjang Pendidikan.
     */
    public function show($id)
    {
        try {
            $level = DB::table('educational_levels')->find($id);

            if (!$level) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            // Menambahkan properti 'duration' agar kompatibel dengan form di JS
            $level->duration = $level->duration_years;

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diambil.',
                'data' => $level
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching educational level ID ' . $id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data.'
            ], 500);
        }
    }

    /**
     * API: Memperbarui data Jenjang Pendidikan.
     */
    public function update(Request $request, $id)
    {
        try {
            if (!DB::table('educational_levels')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            // Validasi input 'duration' dari form
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:educational_levels,name,' . $id,
                'description' => 'nullable|string',
                'duration' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            DB::beginTransaction();

            // Memetakan 'duration' dari request ke kolom 'duration_years' di database
            $updateData = [
                'name' => $request->name,
                'description' => $request->description,
                'duration_years' => $request->duration,
                'updated_at' => now(),
            ];

            DB::table('educational_levels')->where('id', $id)->update($updateData);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Jenjang Pendidikan berhasil diperbarui.']);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating educational level ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
        }
    }

    /**
     * API: Menghapus data Jenjang Pendidikan.
     */
    public function destroy($id)
    {
        try {
            if (!DB::table('educational_levels')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            DB::beginTransaction();
            DB::table('educational_levels')->where('id', $id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Jenjang Pendidikan berhasil dihapus.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting educational level ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }
}

