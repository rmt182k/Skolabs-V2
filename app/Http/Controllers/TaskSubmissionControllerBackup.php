<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // <-- 1. Tambahkan ini
use Exception;
use Carbon\Carbon; // Pastikan Carbon di-import

class TaskSubmissionControllerBackup extends Controller
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
                            $student->status_badge = 'bg-primary';
                            break;
                        case 'late':
                            $student->status_pengerjaan = 'Terlambat (Belum Dinilai)';
                            $student->status_badge = 'bg-warning text-dark';
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
            $totalSubmissions = $submissions->whereIn('status', ['submitted', 'late', 'graded'])->count();
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

        $activeAcademicYear = $this->getActiveAcademicYear();
        if (!$activeAcademicYear) {
            return response()->json(['success' => false, 'message' => 'Tidak ada Tahun Ajaran aktif.'], 500);
        }

        $isEnrolled = DB::table('class_enrollments')
            ->where('class_id', $class_id)
            ->where('student_id', $studentId)
            ->where('academic_year_id', $activeAcademicYear->id)
            ->exists();

        if (!$isEnrolled) {
            return response()->json(['success' => false, 'message' => 'Anda tidak terdaftar di kelas ini.'], 403);
        }

        $payload = $request->json()->all();
        if (empty($payload['answers']) || !is_array($payload['answers'])) {
            return response()->json(['success' => false, 'message' => 'Format jawaban tidak valid.'], 422);
        }

        $task = DB::table('tasks')
            ->where('id', $task_id)
            ->where('class_id', $class_id)
            ->first();

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }

        $now = Carbon::now();
        if ($now->isBefore(Carbon::parse($task->start_time))) {
            return response()->json(['success' => false, 'message' => 'Tugas ini belum dimulai.'], 403);
        }

        // ⭐ PERBAIKAN: Tetap izinkan submit tapi beri status 'late'
        $isLate = $now->isAfter(Carbon::parse($task->end_time));

        $existingSubmission = DB::table('task_submissions')
            ->where('task_id', $task_id)
            ->where('student_id', $studentId)
            ->first();

        if ($existingSubmission) {
            return response()->json(['success' => false, 'message' => 'Anda sudah pernah mengumpulkan tugas ini.'], 409);
        }

        DB::beginTransaction();
        try {
            // ⭐ PERBAIKAN: Status dinamis berdasarkan waktu
            $submissionId = DB::table('task_submissions')->insertGetId([
                'task_id' => $task_id,
                'student_id' => $studentId,
                'submitted_at' => $now,
                'status' => $isLate ? 'late' : 'submitted',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // ⭐ PERBAIKAN: Ambil tipe soal untuk menentukan cara menyimpan
            $questionTypes = DB::table('questions')
                ->where('task_id', $task_id)
                ->pluck('type', 'id'); // [question_id => type]

            $answersToInsert = [];
            foreach ($payload['answers'] as $answer) {
                $questionId = $answer['question_id'];
                $questionType = $questionTypes[$questionId] ?? null;

                if (!$questionType)
                    continue; // Skip jika soal tidak ditemukan

                // ⭐ KUNCI PERBAIKAN: Untuk Multiple Choice, cari option_id dari answer_text
                $questionOptionId = null;
                if ($questionType === 'multiple_choice' && !empty($answer['answer_text'])) {
                    $option = DB::table('question_options')
                        ->where('question_id', $questionId)
                        ->where('option_text', $answer['answer_text'])
                        ->first();

                    if ($option) {
                        $questionOptionId = $option->id;
                    }
                }

                $answersToInsert[] = [
                    'task_submission_id' => $submissionId,
                    'question_id' => $questionId,
                    'question_option_id' => $questionOptionId, // ⭐ Sekarang benar!
                    'answer_text' => $answer['answer_text'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($answersToInsert)) {
                DB::table('task_submission_answers')->insert($answersToInsert);
            }

            // ⭐ Auto-grading (hanya untuk Multiple Choice & Short Answer)
            $this->autoGradeSubmission($submissionId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isLate
                    ? 'Jawaban Anda telah dikumpulkan (Terlambat). Guru akan menilai segera.'
                    : 'Jawaban Anda telah berhasil dikumpulkan!',
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

    private function autoGradeSubmission($submissionId)
    {
        Log::info("Mulai auto-grading untuk submission ID: {$submissionId}");

        $answers = DB::table('task_submission_answers as tsa')
            ->join('questions as q', 'tsa.question_id', '=', 'q.id')
            ->where('tsa.task_submission_id', $submissionId)
            ->select(
                'tsa.id as answer_id',
                'tsa.question_id',
                'tsa.question_option_id',
                'tsa.answer_text',
                'q.type as question_type',
                'q.score as max_score'
            )
            ->get();

        $totalGrade = 0.00;
        $hasPendingManualGrade = false;

        foreach ($answers as $answer) {
            $scoreForThisAnswer = 0.00;

            if ($answer->question_type == 'multiple_choice') {
                // ⭐ SEKARANG question_option_id sudah terisi dengan benar
                if ($answer->question_option_id) {
                    $isCorrect = DB::table('question_options')
                        ->where('id', $answer->question_option_id)
                        ->where('is_correct', true)
                        ->exists();

                    if ($isCorrect) {
                        $scoreForThisAnswer = $answer->max_score;
                    }
                }

                DB::table('task_submission_answers')
                    ->where('id', $answer->answer_id)
                    ->update(['score_awarded' => $scoreForThisAnswer]);

            } else if ($answer->question_type == 'short_answer') {
                // ⭐ BONUS: Auto-grade short answer (exact match)
                $correctAnswer = DB::table('question_options')
                    ->where('question_id', $answer->question_id)
                    ->where('is_correct', true)
                    ->first();

                if ($correctAnswer && strcasecmp(trim($answer->answer_text), trim($correctAnswer->option_text)) === 0) {
                    $scoreForThisAnswer = $answer->max_score;
                }

                DB::table('task_submission_answers')
                    ->where('id', $answer->answer_id)
                    ->update(['score_awarded' => $scoreForThisAnswer]);

            } else if ($answer->question_type == 'essay') {
                // ⭐ Essay perlu penilaian manual
                $hasPendingManualGrade = true;
                DB::table('task_submission_answers')
                    ->where('id', $answer->answer_id)
                    ->update(['score_awarded' => null]);
                continue;
            }

            $totalGrade += $scoreForThisAnswer;
        }

        $finalStatus = $hasPendingManualGrade ? 'submitted' : 'graded';

        DB::table('task_submissions')
            ->where('id', $submissionId)
            ->update([
                'final_grade' => $totalGrade,
                'status' => $finalStatus,
                'updated_at' => Carbon::now()
            ]);

        Log::info("Selesai auto-grading. Submission ID: {$submissionId}, Nilai: {$totalGrade}, Status: {$finalStatus}");
    }

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
            // 1. Ambil info submission utama
            $submission = DB::table('task_submissions as ts')
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

            if (!$submission) {
                return response()->json(['success' => false, 'message' => 'Submission tidak ditemukan.'], 404);
            }

            $submission->submitted_at_formatted = $submission->submitted_at
                ? Carbon::parse($submission->submitted_at)->format('d M Y, H:i')
                : 'N/A';

            // Status text
            switch ($submission->status) {
                case 'submitted':
                    $submission->status_text = 'Terkumpul (Belum Dinilai)';
                    break;
                case 'late':
                    $submission->status_text = 'Terlambat (Belum Dinilai)';
                    break;
                case 'graded':
                    $submission->status_text = 'Sudah Dinilai';
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
                    'tsa.score_awarded',
                    'tsa.teacher_comment',
                    'q.question_text',
                    'q.type as question_type',
                    'q.score as question_score'
                )
                ->get();

            // 3. Untuk setiap jawaban, ambil alokasi kompetensi & evaluasi
            foreach ($answers as $answer) {
                // Ambil alokasi kompetensi untuk soal ini
                $allocations = DB::table('question_competency_allocation as qca')
                    ->join('competencies as c', 'qca.competency_id', '=', 'c.id')
                    ->where('qca.question_id', $answer->question_id)
                    ->select(
                        'qca.competency_id',
                        'qca.max_contribution_score',
                        'c.name as competency_name'
                    )
                    ->get();

                // Ambil evaluasi kompetensi yang sudah ada (jika ada)
                $evaluations = DB::table('answer_competency_evaluations')
                    ->where('task_submission_answer_id', $answer->answer_id)
                    ->pluck('score_awarded', 'competency_id'); // [competency_id => score]

                // Gabungkan
                $answer->competency_allocations = $allocations->map(function ($alloc) use ($evaluations) {
                    $alloc->score_awarded = $evaluations[$alloc->competency_id] ?? 0;
                    return $alloc;
                });

                // Format student_answer untuk tampilan yang lebih baik
                $answer->student_answer = $answer->answer_text
                    ? nl2br(htmlspecialchars($answer->answer_text))
                    : null;
            }

            $data = [
                'submission_id' => $submission->submission_id,
                'task_id' => $submission->task_id,
                'task_title' => $submission->task_title,
                'student_name' => $submission->student_name,
                'student_nis' => $submission->student_nis,
                'submitted_at_formatted' => $submission->submitted_at_formatted,
                'status' => $submission->status,
                'status_text' => $submission->status_text,
                'final_grade' => $submission->final_grade,
                'teacher_feedback' => $submission->teacher_feedback,
                'answers' => $answers
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching submission details for ID ' . $submission_id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memuat data.'], 500);
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

            foreach ($payload['grades'] as $gradeData) {
                $answerId = $gradeData['answer_id'];
                $scoreAwarded = $gradeData['score_awarded'];
                $teacherComment = $gradeData['teacher_comment'] ?? null;

                // Update task_submission_answers
                DB::table('task_submission_answers')
                    ->where('id', $answerId)
                    ->update([
                        'score_awarded' => $scoreAwarded,
                        'teacher_comment' => $teacherComment,
                        'updated_at' => now()
                    ]);

                $totalGrade += $scoreAwarded;

                // Simpan/Update evaluasi kompetensi
                if (!empty($gradeData['competency_evaluations'])) {
                    // Hapus evaluasi lama
                    DB::table('answer_competency_evaluations')
                        ->where('task_submission_answer_id', $answerId)
                        ->delete();

                    // Insert evaluasi baru
                    $evaluationsToInsert = [];
                    foreach ($gradeData['competency_evaluations'] as $compEval) {
                        if ($compEval['score_awarded'] > 0) { // Hanya simpan yang ada skornya
                            $evaluationsToInsert[] = [
                                'task_submission_answer_id' => $answerId,
                                'competency_id' => $compEval['competency_id'],
                                'score_awarded' => $compEval['score_awarded'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                        }
                    }

                    if (!empty($evaluationsToInsert)) {
                        DB::table('answer_competency_evaluations')->insert($evaluationsToInsert);
                    }
                }
            }

            // Update submission utama
            DB::table('task_submissions')
                ->where('id', $submission_id)
                ->update([
                    'final_grade' => $totalGrade,
                    'teacher_feedback' => $payload['teacher_feedback'] ?? null,
                    'status' => 'graded',
                    'updated_at' => now()
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
