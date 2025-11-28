<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Exception;

class LearningMaterialController extends Controller
{
    /**
     * API: Mengambil semua bahan ajar untuk satu kelas.
     * (GET /api/classes/{class_id}/materials)
     */
    public function getMaterials(Request $request, $class_id)
    {
        try {
            $materials = DB::table('learning_materials as lm')
                ->leftJoin('subjects as s', 'lm.subject_id', '=', 's.id')
                ->where('lm.class_id', $class_id)
                ->select(
                    'lm.id',
                    'lm.class_id',
                    'lm.subject_id',
                    'lm.title',
                    'lm.description',
                    'lm.file_path',
                    'lm.file_name',
                    'lm.file_type',
                    'lm.file_size',
                    'lm.link_url',
                    'lm.created_at',
                    's.name as subject_name'
                )
                ->orderBy('lm.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Bahan ajar berhasil diambil.',
                'data' => $materials
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching materials for class ' . $class_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data bahan ajar.',
            ], 500);
        }
    }

    /**
     * API: Menyimpan (membuat atau update) bahan ajar.
     * (POST /api/classes/{class_id}/materials/store)
     */
    public function storeMaterial(Request $request, $class_id)
    {
        // ⭐ DIUBAH: Validasi disederhanakan
        $validator = Validator::make($request->all(), [
            'material_id' => 'nullable|integer|exists:learning_materials,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link_url' => 'nullable|url|max:2048', // Cukup nullable
            'file_input' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar',
                'max:10240' // Maks 10MB
            ],
            'remove_current_file' => 'nullable|boolean',
        ]);

        // Pengecekan kustom: minimal ada 1 lampiran (file/link) atau deskripsi
        // Jika semua kosong (selain title & subject), tolak.
        if (
            !$request->hasFile('file_input') &&
            empty($request->link_url) &&
            empty($request->description)
        ) {
            // Cek apakah ini update dan ada file lama
            $hasOldFile = false;
            if ($request->input('material_id')) {
                 $oldMat = DB::table('learning_materials')->find($request->input('material_id'));
                 if ($oldMat && $oldMat->file_path && !$request->input('remove_current_file')) {
                    $hasOldFile = true;
                 }
            }

            if (!$hasOldFile) {
                 $validator->errors()->add('description', 'Minimal harus ada Deskripsi, File, atau Link.');
                 return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors' => $validator->errors()
                ], 422);
            }
        }


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $material_id = $request->input('material_id');
            $material = $material_id ? DB::table('learning_materials')->find($material_id) : null;

            $dataToSave = [
                'subject_id' => $request->subject_id,
                'title' => $request->title,
                'description' => $request->description,
                'link_url' => $request->link_url, // Simpan link (atau null jika kosong)
                'updated_at' => now(),
            ];

            // ⭐ LOGIKA BARU: Handle Hapus File
            if ($request->input('remove_current_file') == '1' && $material && $material->file_path) {
                Storage::disk('public')->delete($material->file_path);
                $dataToSave['file_path'] = null;
                $dataToSave['file_name'] = null;
                $dataToSave['file_type'] = null;
                $dataToSave['file_size'] = null;
            }

            // ⭐ LOGIKA BARU: Handle Upload File Baru (bisa menimpa)
            if ($request->hasFile('file_input')) {
                // Hapus file lama jika ada (karena diganti file baru)
                if ($material && $material->file_path) {
                    Storage::disk('public')->delete($material->file_path);
                }

                // Simpan file baru
                $file = $request->file('file_input');
                $path = $file->store('learning_materials/' . $class_id, 'public');

                $dataToSave['file_path'] = $path;
                $dataToSave['file_name'] = $file->getClientOriginalName();
                $dataToSave['file_type'] = $file->getClientMimeType();
                $dataToSave['file_size'] = $file->getSize();
            }


            if ($material) {
                // Mode Update
                DB::table('learning_materials')->where('id', $material_id)->update($dataToSave);
                $message = 'Bahan ajar berhasil diperbarui.';
            } else {
                // Mode Create
                $dataToSave['class_id'] = $class_id;
                $dataToSave['created_at'] = now();
                DB::table('learning_materials')->insert($dataToSave);
                $message = 'Bahan ajar berhasil ditambahkan.';
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => $message]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error storing material: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Menghapus bahan ajar.
     * (DELETE /api/classes/{class_id}/materials/{material_id}/destroy)
     */
    public function destroyMaterial($class_id, $material_id)
    {
        DB::beginTransaction();
        try {
            $material = DB::table('learning_materials')
                ->where('id', $material_id)
                ->where('class_id', $class_id)
                ->first();

            if (!$material) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bahan ajar tidak ditemukan.'
                ], 404);
            }

            // Hapus file dari storage jika ada
            if ($material->file_path) {
                Storage::disk('public')->delete($material->file_path);
            }

            // Hapus data dari database
            DB::table('learning_materials')->where('id', $material_id)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bahan ajar berhasil dihapus.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting material ' . $material_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.'
            ], 500);
        }
    }
}
