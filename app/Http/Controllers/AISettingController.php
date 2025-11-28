<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AISettingController extends Controller
{
    public function index()
    {
        return view('ai.index');
    }

    /**
     * [API] Ambil semua settings + models aktif
     */
    public function getSettingsApi()
    {
        try {
            $settings = DB::table('system_ai_settings')
                ->select([
                    'id',
                    'task_key',
                    'task_name',
                    'description',
                    'ai_model_id',
                    'prompt_template',
                    'is_enabled',
                    'created_at',
                    'updated_at'
                ])
                ->orderBy('task_name')
                ->get();

            $models = DB::table('ai_models')
                ->where('is_active', true)
                ->select(['id', 'model_name', 'provider'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                    'models' => $models,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('AI Settings API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data.' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * [API] Bulk update semua settings (termasuk prompt_template)
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'settings' => 'present|array',
            'settings.*.is_enabled' => 'sometimes|in:0,1',
            'settings.*.ai_model_id' => 'nullable|exists:ai_models,id',
            'settings.*.prompt_template' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $allTaskKeys = DB::table('system_ai_settings')->pluck('task_key')->all();

            foreach ($allTaskKeys as $taskKey) {
                $data = $request->input("settings.$taskKey", []);

                $isEnabled = !empty($data['is_enabled']);
                $modelId = $isEnabled && !empty($data['ai_model_id']) ? $data['ai_model_id'] : null;
                $prompt = $data['prompt_template'] ?? null;

                DB::table('system_ai_settings')
                    ->where('task_key', $taskKey)
                    ->update([
                        'is_enabled' => $isEnabled,
                        'ai_model_id' => $modelId,
                        'prompt_template' => $prompt,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semua pengaturan berhasil diperbarui.'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Bulk Update AI Settings Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan perubahan.'
            ], 500);
        }
    }

    /**
     * [API] Tambah tugas AI baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_key' => 'required|string|regex:/^[A-Z0-9_]+$/|max:50|unique:system_ai_settings,task_key',
            'task_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'prompt_template' => 'required|string',
            'ai_model_id' => 'nullable|exists:ai_models,id',
            'is_enabled' => 'sometimes|in:1',
        ], [
            'task_key.regex' => 'Kunci hanya boleh huruf kapital, angka, dan underscore.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['is_enabled'] = !empty($data['is_enabled']);
            $data['ai_model_id'] = $data['is_enabled'] ? ($data['ai_model_id'] ?? null) : null;
            $data['created_at'] = now();
            $data['updated_at'] = now();

            $id = DB::table('system_ai_settings')->insertGetId($data);

            $newSetting = DB::table('system_ai_settings')->find($id);

            return response()->json([
                'success' => true,
                'message' => 'Tugas AI baru berhasil ditambahkan.',
                'data' => $newSetting
            ], 201);
        } catch (Exception $e) {
            Log::error('Store AI Setting Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan tugas.'
            ], 500);
        }
    }

    /**
     * [API] Hapus tugas AI berdasarkan task_key (lebih aman)
     */
    public function destroy($taskKey)
    {
        try {
            $setting = DB::table('system_ai_settings')
                ->where('task_key', $taskKey)
                ->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tugas tidak ditemukan.'
                ], 404);
            }

            DB::table('system_ai_settings')
                ->where('task_key', $taskKey)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Tugas '{$setting->task_name}' berhasil dihapus."
            ]);
        } catch (Exception $e) {
            Log::error('Delete AI Setting Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus tugas.'
            ], 500);
        }
    }
}
