<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class ScheduleController extends Controller
{
    // private function getActiveAcademicYearId() DIHAPUS
    // ... (Fungsi getActiveAcademicYearId() dihapus seluruhnya) ...

    /**
     * API: Mengambil jadwal untuk satu kelas.
     * (GET /api/classes/{class_id}/schedule)
     */
    public function getSchedule(Request $request, $class_id)
    {
        try {
            // $activeAcademicYearId = $this->getActiveAcademicYearId(); // DIHAPUS

            $schedule = DB::table('class_schedules as cs')
                ->leftJoin('subjects as s', 'cs.subject_id', '=', 's.id')
                ->leftJoin('users as u', 'cs.user_id', '=', 'u.id') // 'u' adalah guru
                ->leftJoin('user_details as ud', 'u.id', '=', 'ud.user_id')
                ->where('cs.class_id', $class_id)
                // ->where('cs.academic_year_id', $activeAcademicYearId) // DIHAPUS
                ->select(
                    'cs.id',
                    'cs.day_name',
                    DB::raw("DATE_FORMAT(cs.start_time, '%H:%i') as start_time_formatted"),
                    DB::raw("DATE_FORMAT(cs.end_time, '%H:%i') as end_time_formatted"),
                    'cs.subject_id',
                    's.name as subject_name',
                    'cs.user_id',
                    DB::raw("IF(u.id IS NOT NULL, CONCAT(u.name, ' (', IFNULL(ud.identity_number, 'No ID'), ')'), NULL) as teacher_name")
                )
                ->orderByRaw("FIELD(cs.day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
                ->orderBy('cs.start_time')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil diambil.',
                'data' => $schedule
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching schedule for class ' . $class_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data jadwal.',
            ], 500);
        }
    }

    /**
     * API: Menyimpan (membuat atau update) entri jadwal.
     * (POST /api/classes/{class_id}/schedule/store)
     */
    public function storeScheduleEntry(Request $request, $class_id)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'nullable|integer|exists:class_schedules,id', // Untuk update
            'day_name' => ['required', 'string', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'subject_id' => 'required|integer|exists:subjects,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // $activeAcademicYearId = $this->getActiveAcademicYearId(); // DIHAPUS
            $schedule_id = $request->input('schedule_id');

            // --- Cek Tumpang Tindih (Overlap) ---
            $startTime = $request->start_time;
            $endTime = $request->end_time;
            $dayName = $request->day_name;

            $overlapQuery = DB::table('class_schedules')
                ->where('class_id', $class_id)
                // ->where('academic_year_id', $activeAcademicYearId) // DIHAPUS
                ->where('day_name', $dayName)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime);

            if ($schedule_id) {
                // Jika sedang update, abaikan data diri sendiri
                $overlapQuery->where('id', '!=', $schedule_id);
            }

            if ($overlapQuery->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors' => [
                        'start_time' => ['Jadwal tumpang tindih dengan sesi yang sudah ada.'],
                        'end_time' => ['Jadwal tumpang tindih dengan sesi yang sudah ada.'],
                    ]
                ], 422);
            }
            // --- Akhir Cek Tumpang Tindih ---


            $dataToSave = [
                'class_id' => $class_id,
                // 'academic_year_id' => $activeAcademicYearId, // DIHAPUS
                'day_name' => $request->day_name,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'subject_id' => $request->subject_id,
                'user_id' => $request->user_id,
                'updated_at' => now()
            ];

            DB::beginTransaction();

            if ($schedule_id) {
                // Mode Update
                DB::table('class_schedules')->where('id', $schedule_id)->update($dataToSave);
                $message = 'Jadwal berhasil diperbarui.';
            } else {
                // Mode Create
                $dataToSave['created_at'] = now();
                DB::table('class_schedules')->insert($dataToSave);
                $message = 'Jadwal berhasil ditambahkan.';
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => $message]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error storing schedule entry: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Menghapus entri jadwal.
     * (DELETE /api/classes/{class_id}/schedule/{schedule_id}/destroy)
     */
    public function destroyScheduleEntry($class_id, $schedule_id)
    {
        try {
            $deleted = DB::table('class_schedules')
                ->where('id', $schedule_id)
                ->where('class_id', $class_id) // Pastikan milik kelas yg benar
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Entri jadwal berhasil dihapus.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Entri jadwal tidak ditemukan.'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error deleting schedule entry ' . $schedule_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.'
            ], 500);
        }
    }
}
