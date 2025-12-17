<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;
use Carbon\Carbon;

class TaskController extends Controller
{
    // =========================================================================
    // METODE UNTUK MENAMPILKAN VIEW (HALAMAN)
    // =========================================================================

    public function create(Request $request, $class_id)
    {
        return view('task.formQuestion', ['class_id' => $class_id]);
    }

    /**
     * [BARU] Menampilkan halaman form untuk mengedit tugas.
     * (GET /classes/{class_id}/tasks/{task_id}/edit)
     */
    public function edit(Request $request, $class_id, $task_id)
    {
        // [PERUBAIKAN] Menggunakan tabel 'tasks'
        $taskExists = DB::table('tasks')
            ->where('id', $task_id)
            ->where('class_id', $class_id)
            ->exists();

        if (!$taskExists) {
            abort(404, 'Tugas tidak ditemukan di kelas ini.');
        }

        // View akan mengambil data menggunakan AJAX
        return view('task.formQuestion', [
            'class_id' => $class_id,
            'task_id' => $task_id
        ]);
    }


    // =========================================================================
    // METODE UNTUK API (PENGOLAHAN DATA)
    // =========================================================================

    /**
     * API: Menyimpan tugas baru ke database.
     * (POST /api/classes/{class_id}/tasks/store)
     */
    public function store(Request $request, $class_id)
    {
        // 1. Validasi
        $payload = $request->json()->all();
        // [MODIFIKASI] Kirim class_id ke validator
        $validation = $this->validateTaskPayload($payload, $class_id);
        if ($validation['fails']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
                'errors' => $validation['errors']
            ], 422);
        }

        // 2. Transaksi Database
        DB::beginTransaction();
        try {
            // 3. Simpan 'tasks'
            // [PERUBAIKAN] Menggunakan tabel 'tasks' dan menambah kolom baru
            $taskId = DB::table('tasks')->insertGetId([
                'class_id' => $class_id,
                'subject_id' => $payload['subject_id'],
                'teacher_id' => auth()->id(), // [BARU] Sesuai migrasi
                'title' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'type' => $payload['type'],
                'total_possible_score' => $payload['total_possible_score'],
                'start_time' => Carbon::parse($payload['start_time']),
                'end_time' => Carbon::parse($payload['end_time']),
                'duration_minutes' => $payload['duration_minutes'] ?? null,
                'status' => $payload['status'], // [BARU] Status dinamis
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Simpan 'questions' dan relasinya (menggunakan helper function)
            // Helper ini sudah dimodifikasi untuk tidak menyimpan kompetensi
            $this->storeQuestionsAndRelations($taskId, $payload['questions']);

            // 5. Commit
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tugas berhasil disimpan!',
                'task_id' => $taskId
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error storing task for class ' . $class_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat menyimpan tugas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * [BARU] API: Mengupdate tugas yang sudah ada.
     * (POST /api/classes/{class_id}/tasks/{task_id}/update)
     */
    public function update(Request $request, $class_id, $task_id)
    {
        // 1. Validasi
        $payload = $request->json()->all();
        $validation = $this->validateTaskPayload($payload, $class_id);
        if ($validation['fails']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
                'errors' => $validation['errors']
            ], 422);
        }

        // 2. Cek apakah task-nya ada
        // [PERUBAIKAN] Menggunakan tabel 'tasks'
        $task = DB::table('tasks')->where('id', $task_id)->where('class_id', $class_id)->first();
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }

        // 3. Transaksi Database
        DB::beginTransaction();
        try {
            // 4. Update 'tasks' utama
            // [PERUBAIKAN] Menggunakan tabel 'tasks' dan menambah kolom baru
            DB::table('tasks')->where('id', $task_id)->update([
                'subject_id' => $payload['subject_id'],
                'title' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'type' => $payload['type'],
                'total_possible_score' => $payload['total_possible_score'],
                'start_time' => Carbon::parse($payload['start_time']),
                'end_time' => Carbon::parse($payload['end_time']),
                'duration_minutes' => $payload['duration_minutes'] ?? null,
                'status' => $payload['status'], // [BARU] Status dinamis
                'updated_at' => now(),
            ]);

            // 5. Hapus semua relasi lama
            // Helper ini sudah dimodifikasi untuk tidak menghapus kompetensi
            $this->deleteQuestionsAndRelations($task_id);

            // 6. Buat ulang 'questions' dan relasinya (seperti di 'store')
            $this->storeQuestionsAndRelations($task_id, $payload['questions']);

            // 7. Commit
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tugas berhasil diperbarui!'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating task ' . $task_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat memperbarui tugas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * API: Mengambil semua tugas untuk satu kelas.
     * (GET /api/classes/{class_id}/tasks)
     */
    public function getTasks(Request $request, $class_id)
    {
        try {
            $tasks = DB::table('tasks as t')
                ->leftJoin('subjects as s', 't.subject_id', '=', 's.id')
                ->leftJoin('task_submissions as ts', function ($join) {
                    $join->on('t.id', '=', 'ts.task_id')
                        ->where('ts.student_id', '=', auth()->id());
                })
                ->where('t.class_id', $class_id)
                ->select(
                    't.id',
                    't.title',
                    't.description',
                    't.type',
                    't.start_time',
                    't.end_time',
                    't.total_possible_score',
                    't.status', // [BARU] Status Draft/Published/Closed
                    's.name as subject_name',
                    'ts.status as submission_status', // [BARU] Status pengerjaan (submitted, graded, etc)
                    'ts.id as submission_id' // [BARU] ID submission jika ada
                )
                ->orderBy('t.end_time', 'desc')
                ->get();

            $tasks->each(function ($task) {
                $task->start_time = Carbon::parse($task->start_time)->toIso8601String();
                $task->end_time = Carbon::parse($task->end_time)->toIso8601String();
            });

            return response()->json([
                'success' => true,
                'message' => 'Tugas berhasil diambil.',
                'data' => $tasks
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching tasks for class ' . $class_id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data tugas.'], 500);
        }
    }

    /**
     * [BARU] API: Mengambil data detail satu tugas untuk form Edit.
     * (GET /api/classes/{class_id}/tasks/{task_id}/details)
     */
    public function getTaskDetails(Request $request, $class_id, $task_id)
    {
        try {
            // 1. Ambil data 'task' utama
            // [PERUBAIKAN] Menggunakan tabel 'tasks as t'
            $task = DB::table('tasks as t')
                ->leftJoin('subjects as s', 't.subject_id', '=', 's.id')
                ->where('t.id', $task_id)
                ->where('t.class_id', $class_id)
                ->select('t.*', 's.name as subject_name') // Ambil semua dari task + nama mapel
                ->first();


            if (!$task) {
                return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
            }

            // 2. Format 'task' (terutama waktu)
            $taskData = [
                'title' => $task->title,
                'type' => $task->type,
                'total_possible_score' => (int) $task->total_possible_score,
                'start_time' => Carbon::parse($task->start_time)->format('Y-m-d\TH:i'), // Format untuk datetime-local
                'end_time' => Carbon::parse($task->end_time)->format('Y-m-d\TH:i'),
                'duration_minutes' => $task->duration_minutes,
                'status' => $task->status,
                'description' => $task->description,
                'subject_id' => $task->subject_id,
                'subject_name' => $task->subject_name,
                'questions' => [], // Akan diisi
            ];

            // 3. Ambil 'questions'
            $questions = DB::table('questions')->where('task_id', $task_id)->get();
            $questionIds = $questions->pluck('id');

            // 4. Ambil 'options'
            $options = [];
            // [DIHAPUS] Logika $allocations dihapus

            if ($questionIds->isNotEmpty()) {
                $options = DB::table('question_options')
                    ->whereIn('question_id', $questionIds)
                    ->get()
                    ->groupBy('question_id'); // Kelompokkan berdasarkan question_id

                // [DIHAPUS] Query ke 'question_competency_allocations' dihapus
            }

            // 5. Susun ulang data JSON agar sesuai dengan form
            foreach ($questions as $question) {
                $questionPayload = [
                    'question_text' => $question->question_text,
                    'type' => $question->type,
                    'score' => (int) $question->score,
                    'options' => [],
                    // [DIHAPUS] 'competency_allocations' dihapus
                ];

                // Isi options
                if (isset($options[$question->id])) {
                    foreach ($options[$question->id] as $option) {
                        $questionPayload['options'][] = [
                            'option_text' => $option->option_text,
                            'is_correct' => (bool) $option->is_correct,
                        ];
                    }
                }

                // [DIHAPUS] Logika mengisi 'competency_allocations' dihapus

                $taskData['questions'][] = $questionPayload;
            }

            return response()->json([
                'success' => true,
                'message' => 'Data detail tugas berhasil diambil.',
                'data' => $taskData
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching task details for task ' . $task_id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data detail tugas.'], 500);
        }
    }


    /**
     * API: Menghapus tugas (dan semua data terkaitnya).
     * (DELETE /api/classes/{class_id}/tasks/{task_id}/destroy)
     */
    public function destroyTask($class_id, $task_id)
    {
        // [PERUBAIKAN] Menggunakan tabel 'tasks'
        $taskExists = DB::table('tasks')
            ->where('id', $task_id)
            ->where('class_id', $class_id)
            ->exists();

        if (!$taskExists) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }

        DB::beginTransaction();
        try {
            // Gunakan helper function untuk menghapus (helper sudah dimodifikasi)
            $this->deleteQuestionsAndRelations($task_id);

            // Hapus task_submissions (dan relasi bawahnya)
            $submissionIds = DB::table('task_submissions')->where('task_id', $task_id)->pluck('id');
            if ($submissionIds->isNotEmpty()) {
                $answerIds = DB::table('task_submission_answers')->whereIn('task_submission_id', $submissionIds)->pluck('id');

                if ($answerIds->isNotEmpty()) {
                    // [PERUBAIKAN] Sesuai migrasi, nama tabel ini sudah benar
                    DB::table('answer_competency_evaluations')->whereIn('task_submission_answer_id', $answerIds)->delete();
                }
                DB::table('task_submission_answers')->whereIn('task_submission_id', $submissionIds)->delete();
            }
            DB::table('task_submissions')->where('task_id', $task_id)->delete();

            // Hapus task utama
            // [PERUBAIKAN] Menggunakan tabel 'tasks'
            DB::table('tasks')->where('id', $task_id)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tugas dan semua data terkait telah berhasil dihapus.'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting task ' . $task_id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server saat menghapus.'], 500);
        }
    }


    /**
     * [BARU] API: Mengambil daftar mata pelajaran yang dijadwalkan di kelas ini.
     * (GET /api/classes/{class_id}/scheduled-subjects)
     */
    public function getScheduledSubjects(Request $request, $class_id)
    {
        try {
            $subjects = DB::table('class_schedules as cs')
                ->join('subjects as s', 'cs.subject_id', '=', 's.id')
                ->where('cs.class_id', $class_id)
                ->whereNotNull('cs.subject_id')
                ->select('s.id', 's.name as text') // 'text' untuk format Select2
                ->distinct()
                ->orderBy('s.name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $subjects
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching scheduled subjects for class ' . $class_id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data mata pelajaran.'], 500);
        }
    }


    // =========================================================================
    // FUNGSI HELPER (INTERNAL CONTROLLER)
    // =========================================================================

    /**
     * Helper: Memvalidasi payload untuk 'store' dan 'update'.
     */
    private function validateTaskPayload($payload, $class_id = null)
    {
        $validator = Validator::make($payload, [
            'title' => 'required|string|max:255',
            'subject_id' => 'required|integer',
            'type' => 'required|in:task,quiz,exam',
            'total_possible_score' => 'required|integer|min:0',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'duration_minutes' => 'nullable|integer|min:1',
            'status' => 'required|in:draft,published,closed',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice,essay,short_answer',
            'questions.*.score' => 'required|integer|min:1',
            'questions.*.options' => 'present|array',
            // [DIHAPUS] Validasi 'questions.*.competency_allocations' dihapus
        ]);

        if ($validator->fails()) {
            return ['fails' => true, 'message' => 'Data tidak valid.', 'errors' => $validator->errors()];
        }

        // [BARU] Validasi Kustom: Cek apakah subject_id ada di jadwal kelas
        if ($class_id && isset($payload['subject_id'])) {
            $isValidSubject = DB::table('class_schedules')
                ->where('class_id', $class_id)
                ->where('subject_id', $payload['subject_id'])
                ->exists();

            if (!$isValidSubject) {
                return ['fails' => true, 'message' => "Validasi Gagal: Mata pelajaran yang dipilih tidak terdaftar dalam jadwal kelas ini.", 'errors' => null];
            }
        }


        // [DIHAPUS] Seluruh blok validasi logika kustom untuk kompetensi dihapus.

        return ['fails' => false, 'message' => '', 'errors' => null];
    }

    /**
     * Helper: Menyimpan 'questions' dan relasinya.
     */
    private function storeQuestionsAndRelations($taskId, $questionsPayload)
    {
        foreach ($questionsPayload as $questionData) {
            $questionId = DB::table('questions')->insertGetId([
                'task_id' => $taskId,
                'question_text' => $questionData['question_text'],
                'type' => $questionData['type'],
                'score' => $questionData['score'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Simpan 'question_options'
            if (!empty($questionData['options'])) {
                $optionsToInsert = [];
                foreach ($questionData['options'] as $optionData) {
                    $optionsToInsert[] = [
                        'question_id' => $questionId,
                        'option_text' => $optionData['option_text'],
                        'is_correct' => $optionData['is_correct'] ?? false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('question_options')->insert($optionsToInsert);
            }

            // [DIHAPUS] Blok untuk menyimpan 'question_competency_allocations' dihapus
        }
    }

    /**
     * Helper: Menghapus 'questions' dan relasinya (untuk update/delete).
     */
    private function deleteQuestionsAndRelations($taskId)
    {
        $questionIds = DB::table('questions')->where('task_id', $taskId)->pluck('id');

        if ($questionIds->isNotEmpty()) {
            // [DIHAPUS] Perintah delete untuk 'question_competency_allocations' dihapus
            DB::table('question_options')->whereIn('question_id', $questionIds)->delete();
            DB::table('questions')->whereIn('id', $questionIds)->delete();
        }
    }

    public function getTaskForStudent(Request $request, $class_id, $task_id)
    {
        try {
            // 1. Ambil data 'task' utama
            // [PERUBAIKAN] Menggunakan tabel 'tasks as t'
            $task = DB::table('tasks as t')
                ->join('subjects as s', 't.subject_id', '=', 's.id')
                ->where('t.id', $task_id)
                ->where('t.class_id', $class_id)
                ->select(
                    't.id',
                    't.title',
                    't.description',
                    't.type',
                    't.start_time',
                    't.end_time',
                    't.duration_minutes', // [BARU]
                    't.status',           // [BARU]
                    's.name as subject_name'
                )
                ->first();

            if (!$task) {
                return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
            }

            // 2. Cek waktu & Status
            $now = Carbon::now();
            if ($task->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tugas ini belum diterbitkan atau sudah ditutup.'
                ], 403);
            }

            if ($now->isBefore(Carbon::parse($task->start_time))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tugas ini belum dimulai.'
                ], 403);
            }

            if ($now->isAfter(Carbon::parse($task->end_time))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tugas ini sudah berakhir.'
                ], 403);
            }

            // [BARU] Logika Submission & Started At
            $studentId = auth()->id(); // Asumsi user login adalah siswa

            // Cek apakah sudah ada submission
            $submission = DB::table('task_submissions')
                ->where('task_id', $task_id)
                ->where('student_id', $studentId)
                ->first();

            if (!$submission) {
                // Jika belum ada, buat baru (Mulai Mengerjakan)
                $submissionId = DB::table('task_submissions')->insertGetId([
                    'task_id' => $task_id,
                    'student_id' => $studentId,
                    'started_at' => $now,
                    'status' => 'in_progress',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $startedAt = $now;
            } else {
                // Jika sudah ada, cek status
                if ($submission->status === 'submitted' || $submission->status === 'graded') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda sudah mengumpulkan tugas ini.'
                    ], 403);
                }
                $startedAt = Carbon::parse($submission->started_at);
            }

            // Format waktu untuk ditampilkan
            $task->start_time_formatted = Carbon::parse($task->start_time)->isoFormat('dddd, D MMMM YYYY, HH:mm');
            $task->end_time_formatted = Carbon::parse($task->end_time)->isoFormat('dddd, D MMMM YYYY, HH:mm');


            // 3. Ambil 'questions'
            $questions = DB::table('questions')
                ->where('task_id', $task_id)
                ->select('id', 'question_text', 'type', 'score') // HANYA ambil data aman
                ->get();

            $questionIds = $questions->pluck('id');
            $options = [];

            if ($questionIds->isNotEmpty()) {
                // 4. Ambil 'options' (HANYA teks opsi, BUKAN is_correct)
                $optionsData = DB::table('question_options')
                    ->whereIn('question_id', $questionIds)
                    ->select('id', 'question_id', 'option_text') // HANYA ambil data aman
                    ->get();

                // Acak opsi untuk setiap soal
                $options = $optionsData->groupBy('question_id')->map(function ($group) {
                    return $group->shuffle();
                });
            }

            // 5. Susun ulang data JSON
            $questionsPayload = [];
            foreach ($questions as $question) {
                $questionsPayload[] = [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'type' => $question->type,
                    'score' => $question->score,
                    'options' => $options[$question->id] ?? [],
                ];
            }

            // Acak urutan soal (Opsional)
            // shuffle($questionsPayload);

            $taskData = [
                'details' => $task,
                'questions' => $questionsPayload,
                'user_started_at' => $startedAt->toIso8601String(), // Kirim waktu mulai user
                'server_time' => now()->toIso8601String(), // Kirim waktu server saat ini untuk sinkronisasi
            ];

            return response()->json([
                'success' => true,
                'data' => $taskData,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching student task view for task ' . $task_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data tugas.'
            ], 500);
        }
    }
}
