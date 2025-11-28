<?php

namespace App\Http\Controllers;

use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log; // Gunakan Facades\Log
use Illuminate\Support\Facades\Validator; // Gunakan Facades\Validator

class ManageClassController extends Controller
{
    public function index($classId)
    {
        // ⭐ Cek apakah kelas ada saat menampilkan halaman
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            // Jika tidak ada, lempar ke 404
            abort(404, 'Kelas tidak ditemukan.');
        }

        // Kirim $class (atau minimal classId) ke view
        // Ini akan berguna untuk menampilkan nama kelas, dll.
        return view('manage-class.index', ['class' => $class]);
    }

    /**
     * Helper private untuk mendapatkan T.A. yang aktif.
     */
    private function getActiveAcademicYear()
    {
        return DB::table('academic_years')->where('is_active', true)->first();
    }

    /**
     * Mengambil siswa di kelas berdasarkan T.A. AKTIF.
     */
    public function getStudentsInClass(Request $request, $classId)
    {
        try {
            // 1. Dapatkan T.A. Aktif (Logika Pindah ke Backend)
            $activeAcademicYear = $this->getActiveAcademicYear();

            if (!$activeAcademicYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada Tahun Ajaran yang aktif.',
                    'data' => [] // Kirim data kosong
                ], 400); // 400 Bad Request
            }

            // 2. Ambil siswa (dengan join ke user_details)
            $students = DB::table('class_enrollments')
                ->join('users', 'class_enrollments.student_id', '=', 'users.id')

                // ⭐ PERUBAHAN: Tambahkan leftJoin ke user_details
                // Menggunakan leftJoin agar jika user_details belum ada, data user tetap tampil
                ->leftJoin('user_details', 'users.id', '=', 'user_details.user_id')

                ->where('class_enrollments.class_id', $classId)
                ->where('class_enrollments.academic_year_id', $activeAcademicYear->id)

                ->select(
                    'users.id',
                    'users.name',
                    'user_details.gender',
                    'user_details.identity_number'
                )

                ->orderBy('users.name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Students retrieved successfully.',
                'data' => $students
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching students in class ' . $classId . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching students.' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ⭐ BARU: Menambahkan BANYAK siswa (PLURAL) ke kelas.
     * Ini menggantikan method 'assignStudent' Anda.
     */
    public function assignStudents(Request $request, $classId)
    {
        // 1. Validasi Input (menerima array 'user_ids')
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id', // Cek tiap ID di array
        ], [
            'user_ids.required' => 'Minimal satu siswa harus dipilih.',
            'user_ids.*.exists' => 'Data siswa tidak valid atau tidak ditemukan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Dapatkan T.A. Aktif (Logika di Backend)
        $activeAcademicYear = $this->getActiveAcademicYear();
        if (!$activeAcademicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: Tidak ada Tahun Ajaran (Academic Year) yang sedang aktif.'
            ], 400);
        }

        // Cek apakah kelasnya ada
        if (!DB::table('classes')->where('id', $classId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan.'], 404);
        }

        // Mulai Transaksi
        DB::beginTransaction();
        try {
            $studentIds = $request->input('user_ids');
            $academicYearId = $activeAcademicYear->id;

            $dataToInsert = [];
            $skippedStudentCount = 0;
            $addedStudentCount = 0;
            $now = now();

            // 3. Cek siswa mana yang SUDAH TERDAFTAR di kelas LAIN
            $alreadyEnrolledStudentIds = DB::table('class_enrollments')
                ->where('academic_year_id', $academicYearId)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id')
                ->all();

            foreach ($studentIds as $studentId) {
                // Jika siswa TIDAK ADA di daftar yang sudah terdaftar
                if (!in_array($studentId, $alreadyEnrolledStudentIds)) {
                    $dataToInsert[] = [
                        'class_id' => $classId,
                        'student_id' => $studentId,
                        'academic_year_id' => $academicYearId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } else {
                    $skippedStudentCount++;
                }
            }

            // 4. Bulk Insert data baru jika ada
            if (!empty($dataToInsert)) {
                DB::table('class_enrollments')->insert($dataToInsert);
                $addedStudentCount = count($dataToInsert);
            }

            DB::commit();

            // 5. Buat pesan respons yang informatif
            $message = "Berhasil menambahkan {$addedStudentCount} siswa baru.";
            if ($skippedStudentCount > 0) {
                $message .= " {$skippedStudentCount} siswa dilewati (karena sudah terdaftar di kelas lain pada T.A. ini).";
            }

            return response()->json(['success' => true, 'message' => $message], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error assigning students to class ' . $classId . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan siswa. ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mengeluarkan siswa dari kelas berdasarkan T.A. AKTIF.
     */
    public function removeStudent(Request $request, $classId, $userId)
    {
        DB::beginTransaction();
        try {
            // 1. Dapatkan T.A. Aktif (Logika di Backend)
            $activeAcademicYear = $this->getActiveAcademicYear();
            if (!$activeAcademicYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal: Tidak ada Tahun Ajaran (Academic Year) yang sedang aktif.'
                ], 400);
            }

            // Hapus berdasarkan T.A. aktif
            $affected = DB::table('class_enrollments')
                ->where('class_id', $classId)
                ->where('student_id', $userId)
                ->where('academic_year_id', $activeAcademicYear->id)
                ->delete();

            if ($affected > 0) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Siswa berhasil dikeluarkan dari kelas.'
                ]);
            }

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data pendaftaran siswa tidak ditemukan.'
            ], 404);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error removing student ' . $userId . ' from class ' . $classId . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.'
            ], 500);
        }
    }

    // ========================================================================
    // ⭐ METHOD JADWAL (DIPERBARUI)
    // ========================================================================

    public function getSchedule(Request $request, $classId)
    {
        try {
            $activeAcademicYear = $this->getActiveAcademicYear();
            if (!$activeAcademicYear) {
                return response()->json(['success' => false, 'message' => 'Tahun Ajaran aktif tidak ditemukan.'], 400);
            }

            // ⭐ DIUBAH: Urutkan hari dalam bahasa Inggris
            $dayOrder = "CASE
                WHEN day_name = 'Monday' THEN 1
                WHEN day_name = 'Tuesday' THEN 2
                WHEN day_name = 'Wednesday' THEN 3
                WHEN day_name = 'Thursday' THEN 4
                WHEN day_name = 'Friday' THEN 5
                WHEN day_name = 'Saturday' THEN 6
                ELSE 7
            END";

            $schedule = DB::table('class_schedule_entries')
                ->leftJoin('subjects', 'class_schedule_entries.subject_id', '=', 'subjects.id')
                ->leftJoin('users as teachers', 'class_schedule_entries.teacher_id', '=', 'teachers.id')
                ->where('class_schedule_entries.class_id', $classId)
                ->where('class_schedule_entries.academic_year_id', $activeAcademicYear->id)
                ->select(
                    'class_schedule_entries.id',
                    'class_schedule_entries.day_name',
                    'class_schedule_entries.start_time',
                    'class_schedule_entries.end_time',
                    'class_schedule_entries.subject_id',
                    'class_schedule_entries.teacher_id',
                    'subjects.name as subject_name',
                    'teachers.name as teacher_name'
                )
                ->orderBy(DB::raw($dayOrder)) // Urutkan berdasarkan hari
                ->orderBy('start_time')  // Lalu urutkan berdasarkan jam mulai
                ->get();

            $scheduleWithData = $schedule->map(function ($entry) {
                $entry->start_time_formatted = \Carbon\Carbon::parse($entry->start_time)->format('H:i');
                $entry->end_time_formatted = \Carbon\Carbon::parse($entry->end_time)->format('H:i');
                return $entry;
            });

            return response()->json(['success' => true, 'data' => $scheduleWithData]);

        } catch (Exception $e) {
            Log::error('Error fetching schedule for class ' . $classId . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memuat jadwal.'], 500);
        }
    }

    public function storeScheduleEntry(Request $request, $classId)
    {
        // ⭐ DIUBAH: Validasi hari dalam bahasa Inggris
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'nullable|integer',
            'day_name'    => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'subject_id'  => 'required|integer|exists:subjects,id',
            'teacher_id'  => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        try {
            $activeAcademicYear = $this->getActiveAcademicYear();
            if (!$activeAcademicYear) {
                return response()->json(['success' => false, 'message' => 'Tahun Ajaran aktif tidak ditemukan.'], 400);
            }

            $data = $request->only('day_name', 'start_time', 'end_time', 'subject_id', 'teacher_id');
            $data['academic_year_id'] = $activeAcademicYear->id;
            $data['class_id'] = $classId;
            $data['updated_at'] = now();

            if ($request->filled('schedule_id')) {
                DB::table('class_schedule_entries')
                    ->where('id', $request->schedule_id)
                    ->where('class_id', $classId)
                    ->update($data);
                $message = 'Sesi jadwal berhasil diperbarui.'; // Pesan tetap Bahasa Indonesia
            } else {
                $data['created_at'] = now();
                DB::table('class_schedule_entries')->insert($data);
                $message = 'Sesi jadwal berhasil ditambahkan.'; // Pesan tetap Bahasa Indonesia
            }

            return response()->json(['success' => true, 'message' => $message]);

        } catch (Exception $e) {
            Log::error('Error storing schedule entry: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server.'], 500);
        }
    }

    public function destroyScheduleEntry(Request $request, $classId, $scheduleId)
    {
        try {
            $deleted = DB::table('class_schedule_entries')
                ->where('id', $scheduleId)
                ->where('class_id', $classId)
                ->delete();

            if ($deleted > 0) {
                return response()->json(['success' => true, 'message' => 'Sesi jadwal berhasil dihapus.']);
            } else {
                return response()->json(['success' => false, 'message' => 'Sesi jadwal tidak ditemukan.'], 404);
            }

        } catch (Exception $e) {
            Log::error('Error destroying schedule entry: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server.'], 500);
        }
    }
}
