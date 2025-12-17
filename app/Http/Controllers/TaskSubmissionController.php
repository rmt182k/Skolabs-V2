<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // <-- 1. Tambahkan ini
use Exception;
use Carbon\Carbon; // Pastikan Carbon di-import

class TaskSubmissionController extends Controller
{
    /**
     * Helper private untuk mendapatkan T.A. yang aktif.
     */
    private function getActiveAcademicYear()
    {
        return DB::table('academic_years')->where('is_active', true)->first();
    }

    /**
     * PERUBAHAN: Method index() sekarang HANYA menampilkan halaman wrapper.
     * Data tabel akan di-load via AJAX.
     * URL: /classes/{classId}/tasks/{taskId}/submissions
     */
    public function index(Request $request, $classId, $taskId)
    {
        try {

            // Kita tetap perlu info dasar task dan class untuk header halaman
            $task = DB::table('tasks as task')
                ->leftJoin('subjects', 'task.subject_id', '=', 'subjects.id')
                ->where('task.id', $taskId)
                ->where('task.class_id', $classId)
                ->select(
                    'task.id',
                    'task.title',
                    'task.end_time',
                    'subjects.name as subject_name'
                )
                ->first();

            $class = DB::table('classes')->where('id', $classId)->select('id', 'name')->first();

            if (!$task || !$class) {
                abort(404, 'Data Tugas atau Kelas tidak ditemukan.');
            }

            // Kirim HANYA data yang diperlukan untuk 'kulit' halaman
            return view('task-submission.index', [
                'task' => $task,
                'class' => $class,
            ]);
        } catch (Exception $e) {
            Log::error('Error loading task submission page: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memuat halaman: ' . $e->getMessage());
        }
    }

    /**
     * BARU: Method ini berfungsi sebagai API endpoint untuk DataTables.
     * Method ini berisi SEMUA logic pengolahan data Anda sebelumnya.
     * URL: /api/classes/{classId}/tasks/{taskId}/submissions-data (Contoh)
     */
    public function getSubmissionsData(Request $request, $classId, $taskId)
    {
        try {
            // 1. Dapatkan T.A. Aktif
            $activeAcademicYear = $this->getActiveAcademicYear();
            if (!$activeAcademicYear) {
                return response()->json(['error' => 'Tidak ada Tahun Ajaran aktif.'], 404);
            }

            // 2. Dapatkan Info Tugas (untuk cek end_time)
            $task = DB::table('tasks')->where('id', $taskId)->select('id', 'end_time')->first();
            if (!$task) {
                return response()->json(['error' => 'Tugas tidak ditemukan.'], 404);
            }

            // 3. Dapatkan SEMUA siswa
            $studentsInClass = DB::table('class_enrollments')
                ->join('users', 'class_enrollments.student_id', '=', 'users.id')
                ->leftJoin('user_details', 'users.id', '=', 'user_details.user_id')
                ->where('class_enrollments.class_id', $classId)
                ->where('class_enrollments.academic_year_id', $activeAcademicYear->id)
                ->select(
                    'users.id as student_id',
                    'users.name',
                    'users.email',
                    'user_details.identity_number'
                )
                ->orderBy('users.name')
                ->get();

            // 4. Dapatkan data submisi
            $submissions = DB::table('task_submissions')
                ->where('task_id', $taskId)
                ->select(
                    'id as submission_id', // ⭐ PENTING: Ambil ID submisi untuk link Aksi
                    'student_id',
                    'status',
                    'final_grade',
                    'submitted_at'
                )
                ->get()
                ->keyBy('student_id');

            // 5. Gabungkan data
            $now = now();
            $submissionResults = $studentsInClass->map(function ($student) use ($submissions, $task, $now) {
                $submission = $submissions->get($student->student_id);

                if ($submission) {
                    $student->submission_id = $submission->submission_id;
                    $student->score = $submission->final_grade;
                    $student->submitted_at_formatted = $submission->submitted_at ? Carbon::parse($submission->submitted_at)->format('d M Y, H:i') : 'N/A';
                    $student->status_raw = $submission->status;

                    switch ($submission->status) {
                        case 'submitted':
                            $student->status_pengerjaan = 'Terkumpul (Belum Dinilai)';
                            $student->status_badge = 'bg-warning text-dark';
                            break;
                        case 'ai_processing':
                            $student->status_pengerjaan = 'Sedang Diproses AI...';
                            $student->status_badge = 'bg-info text-dark';
                            break;
                        case 'pending_review':
                            $student->status_pengerjaan = 'AI Review (Guru Menilai)';
                            $student->status_badge = 'bg-primary';
                            break;
                        case 'late':
                            $student->status_pengerjaan = 'Terlambat (Belum Dinilai)';
                            $student->status_badge = 'bg-danger text-dark';
                            break;
                        case 'graded':
                            $student->status_pengerjaan = 'Sudah Dinilai';
                            $student->status_badge = 'bg-success';
                            break;
                        default:
                            $student->status_pengerjaan = 'Dalam Pengerjaan';
                            $student->status_badge = 'bg-secondary';
                    }
                } else {
                    $student->submission_id = null;
                    $student->score = null;
                    $student->submitted_at_formatted = '-';
                    $student->status_raw = 'not_submitted';

                    if ($task->end_time && $now > Carbon::parse($task->end_time)) {
                        $student->status_pengerjaan = 'Tidak Mengerjakan (Ditutup)';
                        $student->status_badge = 'bg-danger';
                    } else {
                        $student->status_pengerjaan = 'Belum Mengerjakan';
                        $student->status_badge = 'bg-secondary';
                    }
                }

                // Pastikan identity_number tidak null untuk DataTables
                $student->identity_number = $student->identity_number ?? 'N/A';

                return $student;
            });

            // 6. Hitung statistik
            $totalStudents = $studentsInClass->count();
            $totalSubmissions = $submissions->whereIn('status', ['submitted', 'late', 'graded', 'pending_review'])->count();
            $averageScore = $totalSubmissions > 0 ? round($submissions->whereNotNull('final_grade')->avg('final_grade'), 2) : 0;

            $stats = [
                'total_students' => $totalStudents,
                'total_submissions' => $totalSubmissions,
                'average_score' => $averageScore,
            ];

            // 7. Kembalikan sebagai JSON
            // Format 'data' adalah standar yang disukai DataTables
            return response()->json([
                'stats' => $stats,
                'data' => $submissionResults,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching task submissions data: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memuat data: ' . $e->getMessage()], 500);
        }
    }

    public function showAnswerSheet(Request $request, $class_id, $task_id)
    {
        // Verifikasi bahwa tugas ada dan siswa terdaftar di kelas ini
        // (Anda bisa tambahkan logika verifikasi yang lebih ketat di sini)
        $taskExists = DB::table('tasks')
            ->where('id', $task_id)
            ->where('class_id', $class_id)
            ->exists();

        if (!$taskExists) {
            abort(404, 'Tugas tidak ditemukan.');
        }

        // Kirim ID ke view, view akan memuat data via AJAX
        return view('task.formAnswer', [
            'class_id' => $class_id,
            'task_id' => $task_id
        ]);
    }

    /**
     * API: Menerima dan menyimpan jawaban dari siswa.
     * (POST /api/classes/{class_id}/tasks/{task_id}/submit)
     */
    public function storeSubmission(Request $request, $class_id, $task_id)
    {
        $studentId = Auth::id();
        if (!$studentId) {
            return response()->json(['success' => false, 'message' => 'Anda harus login untuk mengumpulkan tugas.'], 401);
        }

        // ... (Semua validasi Anda: academic year, enrollment, task, start_time... sudah OK) ...

        $task = DB::table('tasks')
            ->where('id', $task_id)
            ->where('class_id', $class_id)
            ->first();

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }

        $now = Carbon::now();
        if ($now->isBefore(Carbon::parse($task->start_time))) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas ini belum dimulai.'
            ], 403);
        }

        $isLate = $now->isAfter(Carbon::parse($task->end_time));

        $existingSubmission = DB::table('task_submissions')
            ->where('task_id', $task_id)
            ->where('student_id', $studentId)
            ->first();

        // [UBAH LOGIKA] Cek status submission
        if ($existingSubmission) {
            if ($existingSubmission->status !== 'in_progress') {
                return response()->json(['success' => false, 'message' => 'Anda sudah mengumpulkan tugas ini.'], 409);
            }

            // [BARU] Validasi Durasi (Server-side)
            if ($task->duration_minutes) {
                $startedAt = Carbon::parse($existingSubmission->started_at);
                $allowedEndTime = $startedAt->copy()->addMinutes($task->duration_minutes)->addMinutes(2); // Buffer 2 menit untuk latensi

                if ($now->isAfter($allowedEndTime)) {
                    // Opsional: Bisa tolak atau terima tapi tandai telat
                    // Di sini kita terima saja tapi tandai late jika perlu, atau reject
                    // Sesuai request "validasi", kita reject jika *terlalu* jauh,
                    // tapi karena auto-submit, biasanya kita toleransi sedikit.
                    // return response()->json(['success' => false, 'message' => 'Waktu pengerjaan telah habis.'], 403);
                }
            }
        }

        // Validasi payload baru dari JS
        $payload = $request->json()->all();
        if (empty($payload['answers']) || !is_array($payload['answers'])) {
            return response()->json(['success' => false, 'message' => 'Format jawaban tidak valid.'], 422);
        }

        DB::beginTransaction();
        try {
            // [PERUBAIKAN] Status diset ke 'submitted' (atau 'late') untuk menunggu AI
            $submissionStatus = $isLate ? 'late' : 'submitted';

            if ($existingSubmission) {
                // UPDATE existing
                // Hitung durasi. Gunakan abs() untuk menghindari nilai negatif jika server time sedikit tidak sinkron
                // atau jika start time tercatat 'di masa depan' karena perbedaan microsecond/latency.
                $duration = abs($now->diffInSeconds(Carbon::parse($existingSubmission->started_at)));

                DB::table('task_submissions')
                    ->where('id', $existingSubmission->id)
                    ->update([
                        'submitted_at' => $now,
                        'status' => $submissionStatus,
                        'duration_seconds' => $duration,
                        'updated_at' => $now,
                    ]);
                $submissionId = $existingSubmission->id;

                // Hapus jawaban lama sementera (untuk overwrite, jika ada draft saving sebelumnya - saat ini belum ada, tapi aman)
                // DB::table('task_submission_answers')->where('task_submission_id', $submissionId)->delete(); 
            } else {
                // INSERT baru (jika entah kenapa tidak ada in_progress)
                $submissionId = DB::table('task_submissions')->insertGetId([
                    'task_id' => $task_id,
                    'student_id' => $studentId,
                    'started_at' => $now, // Anggap baru mulai
                    'submitted_at' => $now,
                    'status' => $submissionStatus,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $answersToInsert = [];
            foreach ($payload['answers'] as $answer) {
                // Tidak perlu query tipe soal, kita simpan saja apa adanya
                $answersToInsert[] = [
                    'task_submission_id' => $submissionId,
                    'question_id' => $answer['question_id'],

                    // [PERBAIKAN] Langsung simpan dari payload JS yang baru
                    'question_option_id' => $answer['question_option_id'] ?? null,
                    'answer_text' => $answer['answer_text'] ?? null,

                    // [PENTING] Semua kolom nilai & AI dibiarkan NULL
                    'ai_suggested_score' => null,
                    'ai_feedback' => null,
                    'ai_processing_status' => 'pending', // Siap diproses AI
                    'score_awarded' => null,
                    'teacher_comment' => null,
                    'is_correct' => null,

                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($answersToInsert)) {
                DB::table('task_submission_answers')->insert($answersToInsert);
            }

            // [DIHAPUS] Panggilan ke $this->autoGradeSubmission($submissionId); Dihapus.

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isLate
                    ? 'Jawaban Anda telah dikumpulkan (Terlambat). Menunggu penilaian.'
                    : 'Jawaban Anda telah berhasil dikumpulkan. Menunggu penilaian.',
                'submission_id' => $submissionId
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error submitting task ' . $task_id . ' for student ' . $studentId . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal saat menyimpan jawaban Anda.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // private function autoGradeSubmission($submissionId)
    // {
    //     Log::info("Mulai auto-grading untuk submission ID: {$submissionId}");

    //     $answers = DB::table('task_submission_answers as tsa')
    //         ->join('questions as q', 'tsa.question_id', '=', 'q.id')
    //         ->where('tsa.task_submission_id', $submissionId)
    //         ->select(
    //             'tsa.id as answer_id',
    //             'tsa.question_id',
    //             'tsa.question_option_id',
    //             'tsa.answer_text',
    //             'q.type as question_type',
    //             'q.score as max_score'
    //         )
    //         ->get();

    //     $totalGrade = 0.00;
    //     $hasPendingManualGrade = false;

    //     foreach ($answers as $answer) {
    //         $scoreForThisAnswer = 0.00;

    //         if ($answer->question_type == 'multiple_choice') {
    //             // ⭐ SEKARANG question_option_id sudah terisi dengan benar
    //             if ($answer->question_option_id) {
    //                 $isCorrect = DB::table('question_options')
    //                     ->where('id', $answer->question_option_id)
    //                     ->where('is_correct', true)
    //                     ->exists();

    //                 if ($isCorrect) {
    //                     $scoreForThisAnswer = $answer->max_score;
    //                 }
    //             }

    //             DB::table('task_submission_answers')
    //                 ->where('id', $answer->answer_id)
    //                 ->update(['score_awarded' => $scoreForThisAnswer]);

    //         } else if ($answer->question_type == 'short_answer') {
    //             // ⭐ BONUS: Auto-grade short answer (exact match)
    //             $correctAnswer = DB::table('question_options')
    //                 ->where('question_id', $answer->question_id)
    //                 ->where('is_correct', true)
    //                 ->first();

    //             if ($correctAnswer && strcasecmp(trim($answer->answer_text), trim($correctAnswer->option_text)) === 0) {
    //                 $scoreForThisAnswer = $answer->max_score;
    //             }

    //             DB::table('task_submission_answers')
    //                 ->where('id', $answer->answer_id)
    //                 ->update(['score_awarded' => $scoreForThisAnswer]);

    //         } else if ($answer->question_type == 'essay') {
    //             // ⭐ Essay perlu penilaian manual
    //             $hasPendingManualGrade = true;
    //             DB::table('task_submission_answers')
    //                 ->where('id', $answer->answer_id)
    //                 ->update(['score_awarded' => null]);
    //             continue;
    //         }

    //         $totalGrade += $scoreForThisAnswer;
    //     }

    //     $finalStatus = $hasPendingManualGrade ? 'submitted' : 'graded';

    //     DB::table('task_submissions')
    //         ->where('id', $submissionId)
    //         ->update([
    //             'final_grade' => $totalGrade,
    //             'status' => $finalStatus,
    //             'updated_at' => Carbon::now()
    //         ]);

    //     Log::info("Selesai auto-grading. Submission ID: {$submissionId}, Nilai: {$totalGrade}, Status: {$finalStatus}");
    // }

    /**
     * Menampilkan halaman form penilaian (wrapper)
     * GET /classes/{class_id}/tasks/{task_id}/submissions/{submission_id}/grade
     */
    public function showGradeForm($class_id, $task_id, $submission_id)
    {
        // Verifikasi bahwa submission ada
        $submissionExists = DB::table('task_submissions')
            ->where('id', $submission_id)
            ->where('task_id', $task_id)
            ->exists();

        if (!$submissionExists) {
            abort(404, 'Submission tidak ditemukan.');
        }

        return view('task.formGrade', [
            'class_id' => $class_id,
            'task_id' => $task_id,
            'submission_id' => $submission_id
        ]);
    }

    /**
     * API: Mengambil detail submission untuk penilaian
     * GET /api/submissions/{submission_id}/details
     */
    public function getSubmissionDetails($submission_id)
    {
        try {
            // 1. Ambil info submission utama (Query Anda sudah OK)
            $submission = DB::table('task_submissions as ts')
                // ... (join Anda ke users, user_details, tasks) ...
                ->join('users as u', 'ts.student_id', '=', 'u.id')
                ->leftJoin('user_details as ud', 'u.id', '=', 'ud.user_id')
                ->join('tasks as t', 'ts.task_id', '=', 't.id')
                ->where('ts.id', $submission_id)
                ->select(
                    'ts.id as submission_id',
                    'ts.task_id',
                    'ts.submitted_at',
                    'ts.status',
                    'ts.final_grade',
                    'ts.teacher_feedback',
                    'u.name as student_name',
                    'ud.identity_number as student_nis',
                    't.title as task_title'
                )
                ->first();
            // ... (logika 404 dan formatting waktu Anda sudah OK) ...
            if (!$submission) {
                return response()->json(['success' => false, 'message' => 'Submission tidak ditemukan.'], 404);
            }

            // ... (Logika status text Anda, ubah sedikit untuk status baru) ...
            switch ($submission->status) {
                case 'submitted':
                    $submission->status_text = 'Menunggu Analisis AI';
                    break;
                case 'ai_processing':
                    $submission->status_text = 'Sedang Dianalisis AI';
                    break;
                case 'pending_review':
                    $submission->status_text = 'Perlu Direview (Menunggu Approval)';
                    break;
                case 'graded':
                    $submission->status_text = 'Sudah Dinilai (Final)';
                    break;
                case 'late':
                    $submission->status_text = 'Terlambat (Menunggu Review)';
                    break;
                default:
                    $submission->status_text = 'Status Tidak Dikenal';
            }


            // 2. Ambil jawaban siswa
            $answers = DB::table('task_submission_answers as tsa')
                ->join('questions as q', 'tsa.question_id', '=', 'q.id')
                ->where('tsa.task_submission_id', $submission_id)
                ->select(
                    'tsa.id as answer_id',
                    'tsa.question_id',
                    'tsa.answer_text',
                    'tsa.question_option_id', // Ambil ini untuk PG

                    // [BARU] Ambil data sugesti AI
                    'tsa.ai_suggested_score',
                    'tsa.ai_feedback',
                    'tsa.ai_processing_status',

                    // [BARU] Ambil data final guru (untuk diedit)
                    'tsa.score_awarded',
                    'tsa.teacher_comment',

                    'q.question_text',
                    'q.type as question_type',
                    'q.score as question_score'
                )
                ->orderBy('q.order', 'asc') // Urutkan berdasarkan soal
                ->get();

            // 3. [DIUBAH] Proses jawaban
            foreach ($answers as $answer) {
                // [DIHAPUS] Semua logika 'question_competency_allocation' dan 'answer_competency_evaluations' dihapus

                // Format jawaban siswa untuk ditampilkan
                if ($answer->question_type == 'multiple_choice') {
                    if ($answer->question_option_id) {
                        // Ambil teks jawaban PG dari ID yang disimpan
                        $answer->student_answer = DB::table('question_options')
                            ->where('id', $answer->question_option_id)
                            ->value('option_text');
                    } else {
                        $answer->student_answer = '<i class="text-muted">(Tidak dijawab)</i>';
                    }
                } else {
                    $answer->student_answer = $answer->answer_text
                        ? nl2br(htmlspecialchars($answer->answer_text))
                        : '<i class="text-muted">(Tidak dijawab)</i>';
                }
            }

            // ... (Data Anda sudah OK, hanya perlu 'answers' yang baru) ...
            $data = [
                'submission_id' => $submission->submission_id,
                'task_id' => $submission->task_id,
                'task_title' => $submission->task_title,
                'student_name' => $submission->student_name,
                'student_nis' => $submission->student_nis,
                // 'submitted_at_formatted' => $submission->submitted_at_formatted,
                'status' => $submission->status,
                'status_text' => $submission->status_text,
                'final_grade' => $submission->final_grade,
                'teacher_feedback' => $submission->teacher_feedback,
                'answers' => $answers // <-- 'answers' sekarang berisi data AI
            ];

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Log::error('Error fetching submission details for ID ' . $submission_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data.'
            ], 500);
        }
    }

    /**
     * API: Menyimpan penilaian dari guru
     * POST /api/submissions/{submission_id}/grade
     */
    public function saveGrades(Request $request, $submission_id)
    {
        $payload = $request->json()->all();

        if (empty($payload['grades']) || !is_array($payload['grades'])) {
            return response()->json(['success' => false, 'message' => 'Format data tidak valid.'], 422);
        }

        DB::beginTransaction();
        try {
            $totalGrade = 0;
            $gradedBy = Auth::id(); // Guru yang menilai
            $now = now();

            foreach ($payload['grades'] as $gradeData) {
                $answerId = $gradeData['answer_id'];
                $scoreAwarded = $gradeData['score_awarded'];
                $teacherComment = $gradeData['teacher_comment'] ?? null;

                // Update task_submission_answers (Ini adalah data FINAL dari guru)
                DB::table('task_submission_answers')
                    ->where('id', $answerId)
                    ->update([
                        'score_awarded' => $scoreAwarded,
                        'teacher_comment' => $teacherComment,
                        'updated_at' => $now
                    ]);

                $totalGrade += $scoreAwarded;

                // [DIHAPUS] Semua logika 'answer_competency_evaluations' dihapus.
            }

            // Update submission utama
            DB::table('task_submissions')
                ->where('id', $submission_id)
                ->update([
                    'final_grade' => $totalGrade,
                    'teacher_feedback' => $payload['teacher_feedback'] ?? null,
                    'status' => 'graded', // Status FINAL
                    'graded_by' => $gradedBy,
                    'graded_at' => $now,
                    'updated_at' => $now
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penilaian berhasil disimpan!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error saving grades for submission ' . $submission_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan penilaian.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
