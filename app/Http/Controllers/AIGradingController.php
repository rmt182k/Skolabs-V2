<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;
use Carbon\Carbon;

class AIGradingController extends Controller
{
    private const AI_PROVIDERS = [
        'claude' => [
            'name' => 'Anthropic Claude',
            'models' => [
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Recommended)',
                'claude-3-opus-20240229' => 'Claude 3 Opus (Most Accurate)',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku (Fastest)',
            ],
            'default_model' => 'claude-3-5-sonnet-20241022'
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'models' => [
                'gemini-2.5-pro' => 'Gemini 2.5 Pro (Recommended)',
                'gemini-2.0-flash' => 'Gemini 2.0 Flash',
                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            ],
            'default_model' => 'gemini-2.0-flash'
        ],
        'openai' => [
            'name' => 'OpenAI GPT',
            'models' => [
                'gpt-4o' => 'GPT-4o (Recommended)',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
            ],
            'default_model' => 'gpt-4o'
        ]
    ];

    /**
     * API: Memulai proses analisis AI untuk submission
     * POST /api/submissions/{submission_id}/run-ai
     */
    public function runAIAnalysis(Request $request, $submission_id)
    {
        DB::beginTransaction();
        try {
            $provider = $request->input('provider', env('AI_GRADING_PROVIDER', 'gemini'));
            $model = $request->input('model', null);

            if (!isset(self::AI_PROVIDERS[$provider])) {
                return response()->json([
                    'success' => false,
                    'message' => "Provider '{$provider}' tidak tersedia"
                ], 400);
            }

            // 1. VALIDASI SUBMISSION
            $submission = DB::table('task_submissions as ts')
                ->join('tasks as t', 'ts.task_id', '=', 't.id')
                ->join('users as u', 'ts.student_id', '=', 'u.id')
                ->leftJoin('user_details as ud', 'u.id', '=', 'ud.user_id')
                ->where('ts.id', $submission_id)
                ->select(
                    'ts.id',
                    'ts.task_id',
                    'ts.student_id',
                    'ts.status',
                    'ts.submitted_at',
                    't.title as task_title',
                    't.description as task_description',
                    't.type as task_type',
                    'u.name as student_name',
                    'u.email',
                    'ud.identity_number as student_nis'
                )
                ->first();

            if (!$submission) {
                return response()->json(['success' => false, 'message' => 'Submission tidak ditemukan'], 404);
            }

            if (!in_array($submission->status, ['submitted', 'late'])) {
                return response()->json(['success' => false, 'message' => 'Status tidak valid'], 400);
            }

            // 2. UPDATE STATUS KE AI_PROCESSING
            DB::table('task_submissions')->where('id', $submission_id)->update([
                'status' => 'ai_processing',
                'updated_at' => now()
            ]);

            // 3. AMBIL JAWABAN + KOMPETENSI
            $answers = DB::table('task_submission_answers as tsa')
                ->join('questions as q', 'tsa.question_id', '=', 'q.id')
                ->leftJoin('question_options as qo', function ($join) {
                    $join->on('q.id', '=', 'qo.question_id')->where('qo.is_correct', true);
                })
                ->leftJoin('question_competency_allocations as qca', 'q.id', '=', 'qca.question_id')
                ->leftJoin('competencies as c', 'qca.competency_id', '=', 'c.id')
                ->where('tsa.task_submission_id', $submission_id)
                ->where('tsa.ai_processing_status', 'pending')
                ->select(
                    'tsa.id as answer_id',
                    'tsa.question_id',
                    'tsa.answer_text',
                    'tsa.question_option_id',
                    'q.question_text',
                    'q.type as question_type',
                    'q.score as max_score',
                    'q.order',
                    'q.explanation',
                    'qo.option_text as correct_answer_text',
                    'qca.competency_id',
                    'c.name as comp_name',
                    'c.description as comp_desc',
                    'qca.max_contribution_score',
                    'qca.weight_percentage'
                )
                ->orderBy('q.order')
                ->get();

            if ($answers->isEmpty()) {
                $this->finalizeEmptySubmission($submission_id);
                DB::commit(); // Commit transaksi untuk submission kosong
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada jawaban untuk diproses',
                    'data' => $this->getFullReportData($submission_id)
                ]);
            }

            // 4. PROCESS AI
            $totalScore = 0;
            $competencyScores = [];
            $processedCount = 0;
            $failedCount = 0;

            Log::info("AI processing started for submission {$submission_id}");

            foreach ($answers->groupBy('answer_id') as $answerGroup) {
                $answer = $answerGroup->first();
                $competencies = $answerGroup->whereNotNull('competency_id');

                try {
                    // ==========================================================
                    // PERMINTAAN USER: Jangan gunakan autoGradeMultipleChoice,
                    // komentari methodnya dan gunakan AI untuk semua tipe soal.
                    // ==========================================================
                    /*
                    if ($answer->question_type === 'multiple_choice') {
                        $result = $this->autoGradeMultipleChoice($answer);
                    } else {
                        $result = $this->callAIEngine($provider, $answer, $submission, $model);
                    }
                    */

                    // BARU: Selalu panggil AI Engine untuk semua tipe soal
                    $result = $this->callAIEngine($provider, $answer, $submission, $model);
                    // ==========================================================


                    if ($result['success']) {
                        // UPDATE JAWABAN (FIX: SET score_awarded & teacher_comment)
                        DB::table('task_submission_answers')
                            ->where('id', $answer->answer_id)
                            ->update([
                                'ai_suggested_score' => $result['score'],
                                'ai_feedback' => $result['feedback'],
                                // Ubah 'raw_data' menjadi 'raw_results' agar konsisten
                                'ai_raw_results' => json_encode($result['raw_results']),
                                'ai_processing_status' => 'completed',
                                'score_awarded' => $result['score'],      // ✅ FIXED
                                'teacher_comment' => $result['feedback'],  // ✅ FIXED
                                'is_correct' => $result['is_correct'] ?? null,
                                'updated_at' => now()
                            ]);

                        $totalScore += $result['score'];
                        $processedCount++;

                        // ALLOCATE KE KOMPETENSI
                        $this->allocateToCompetencies($competencies, $result['score'], $answer->max_score, $competencyScores);

                    } else {
                        // Ini adalah penanganan jika AI API mengembalikan error (misal: safety block)
                        // Jawaban ditandai 'failed' agar tidak diproses ulang.
                        DB::table('task_submission_answers')
                            ->where('id', $answer->answer_id)
                            ->update([
                                'ai_processing_status' => 'failed',
                                'ai_feedback' => 'AI Error: ' . $result['error'],
                                'updated_at' => now()
                            ]);
                        $failedCount++;
                    }

                } catch (Exception $e) {
                    // Ini adalah penanganan jika terjadi exception (misal: timeout)
                    // Tidak ada data yang disimpan, status tetap 'pending' (kecuali di-handle di luar)
                    Log::error("Error processing answer {$answer->answer_id}: " . $e->getMessage());
                    $failedCount++;

                    // Jika Anda ingin *satu* kegagalan jawaban menggagalkan *seluruh* submission,
                    // aktifkan baris di bawah ini untuk melempar exception ke catch utama.
                    // throw $e;
                }
            }

            // 5. HITUNG ULANG FINAL GRADE DARI score_awarded (FIX)
            // Ini menghitung ulang total skor dari *semua* jawaban yang memiliki nilai,
            // termasuk yang sudah dinilai sebelumnya.
            $finalTotalScore = DB::table('task_submission_answers')
                ->where('task_submission_id', $submission_id)
                ->whereNotNull('score_awarded')
                ->sum('score_awarded');

            // 6. SIMPAN KOMPETENSI & REKOMENDASI
            $this->saveCompetencyScores($submission_id, $competencyScores);
            $recommendations = $this->generateRecommendations($competencyScores);
            $this->saveRecommendations($submission_id, $recommendations);

            // 7. FINALIZE
            $this->finalizeSubmission($submission_id, $finalTotalScore);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "AI selesai! {$processedCount} berhasil, {$failedCount} gagal",
                'data' => $this->getFullReportData($submission_id),
                'stats' => compact('processedCount', 'failedCount', 'finalTotalScore', 'provider')
            ]);

        } catch (Exception $e) {
            // Ini adalah catch utama. Jika terjadi error di sini (misal: DB error, atau exception
            // yang dilempar ulang dari loop), seluruh transaksi akan di-rollback.
            DB::rollBack();

            // Kembalikan status submission ke 'submitted' agar bisa dicoba lagi
            // Cek dulu apakah submission masih ada
            if (DB::table('task_submissions')->where('id', $submission_id)->exists()) {
                DB::table('task_submissions')->where('id', $submission_id)->update([
                    'status' => 'submitted',
                    'updated_at' => now()
                ]);
            }

            Log::error("AI Error (Rollback) for submission {$submission_id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => "Proses AI Gagal (Rollback): " . $e->getMessage()
            ], 500);
        }
    }

    public function retrySingleAnswerAnalysis(Request $request, $submission_id, $answer_id)
    {
        DB::beginTransaction();
        try {
            $provider = $request->input('provider', env('AI_GRADING_PROVIDER', 'gemini'));

            // 1. Ambil Data Jawaban Spesifik
            $answer = DB::table('task_submission_answers as tsa')
                ->join('questions as q', 'tsa.question_id', '=', 'q.id')
                ->leftJoin('question_options as qo', function ($join) {
                    $join->on('q.id', '=', 'qo.question_id')->where('qo.is_correct', true);
                })
                ->where('tsa.id', $answer_id)
                ->where('tsa.task_submission_id', $submission_id)
                ->select(
                    'tsa.id as answer_id',
                    'tsa.question_id',
                    'tsa.answer_text',
                    'tsa.question_option_id',
                    'q.question_text',
                    'q.type as question_type',
                    'q.score as max_score',
                    'q.explanation',
                    'qo.option_text as correct_answer_text'
                )
                ->first();

            if (!$answer) {
                return response()->json(['success' => false, 'message' => 'Jawaban tidak ditemukan'], 404);
            }

            // 2. Ambil Data Submission (untuk Konteks Prompt)
            $submission = DB::table('task_submissions as ts')
                ->join('tasks as t', 'ts.task_id', '=', 't.id')
                ->join('users as u', 'ts.student_id', '=', 'u.id')
                ->where('ts.id', $submission_id)
                ->select('t.title as task_title', 't.description as task_description', 'u.name as student_name')
                ->first();

            // 3. Panggil AI Engine (Single Call)
            // Pastikan method callAIEngine sudah ada di controller ini
            $result = $this->callAIEngine($provider, $answer, $submission);

            if ($result['success']) {
                // 4. Update Jawaban
                DB::table('task_submission_answers')
                    ->where('id', $answer_id)
                    ->update([
                        'ai_suggested_score' => $result['score'],
                        'ai_feedback' => $result['feedback'],
                        'ai_raw_results' => json_encode($result['raw_results']),
                        'ai_processing_status' => 'completed',
                        'updated_at' => now()
                    ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil generate ulang.',
                    'data' => [
                        'ai_suggested_score' => $result['score'],
                        'ai_feedback' => $result['feedback']
                    ]
                ]);
            } else {
                throw new Exception($result['error'] ?? 'Gagal menghubungi AI');
            }

        } catch (Exception $e) {
            DB::rollBack();
            // Tandai failed agar tombol retry muncul
            DB::table('task_submission_answers')->where('id', $answer_id)->update([
                'ai_processing_status' => 'failed',
                'ai_feedback' => 'Error: ' . $e->getMessage()
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ========== HELPER METHODS ==========

    /*
    // ==========================================================
    // PERMINTAAN USER: autoGradeMultipleChoice dinonaktifkan
    // dan digantikan dengan penilaian AI.
    // Method ini dikomentari, tidak dihapus.
    // ==========================================================
    private function autoGradeMultipleChoice($answer)
    {
        $isCorrect = $answer->question_option_id &&
            DB::table('question_options')
                ->where('id', $answer->question_option_id)
                ->where('is_correct', true)
                ->exists();

        $score = $isCorrect ? $answer->max_score : 0;

        return [
            'success' => true,
            'score' => $score,
            'feedback' => $isCorrect
                ? 'Jawaban benar!'
                : 'Jawaban salah. Jawaban benar: ' . ($answer->correct_answer_text ?? 'N/A'),
            'is_correct' => $isCorrect,
            'raw_results' => [ // Diubah dari 'raw_data'
                'provider' => 'auto_grade',
                'type' => 'multiple_choice',
                'is_correct' => $isCorrect
            ]
        ];
    }
    */

    private function allocateToCompetencies($competencies, $earned, $max, &$competencyScores)
    {
        if ($max <= 0)
            return; // Mencegah pembagian dengan nol

        foreach ($competencies as $c) {
            $ratio = $earned / $max;
            $contrib = ($c->max_contribution_score ?? 0) * $ratio * (($c->weight_percentage ?? 100) / 100);
            $competencyScores[$c->competency_id] = ($competencyScores[$c->competency_id] ?? 0) + $contrib;
        }
    }

    private function saveCompetencyScores($submission_id, $scores)
    {
        // Pastikan $statId ada sebelum loop
        $statId = DB::table('submission_statistics')->updateOrInsert(
            ['task_submission_id' => $submission_id],
            [
                'total_questions' => DB::table('task_submission_answers')->where('task_submission_id', $submission_id)->count(),
                'updated_at' => now(),
                'created_at' => now() // Pastikan created_at diisi saat insert
            ]
        );

        // Jika updateOrInsert tidak mengembalikan ID, ambil manual
        if (!$statId) {
            $statRecord = DB::table('submission_statistics')->where('task_submission_id', $submission_id)->first();
            $statId = $statRecord ? $statRecord->id : null;
        }

        // Tambahkan pengecekan jika statId masih null
        if (!$statId) {
            Log::error("Failed to create or find submission_statistic for submission_id: {$submission_id}");
            return; // Keluar dari fungsi jika tidak ada statId
        }

        foreach ($scores as $compId => $score) {
            DB::table('submission_competency_scores')->updateOrInsert(
                ['submission_statistic_id' => $statId, 'competency_id' => $compId],
                ['score' => $score, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    private function generateRecommendations($competencyScores)
    {
        $recommendations = [];
        foreach ($competencyScores as $compId => $score) {
            $comp = DB::table('competencies')->where('id', $compId)->first();
            if (!$comp)
                continue; // Lewati jika kompetensi tidak ditemukan

            $max = DB::table('question_competency_allocations')
                ->where('competency_id', $compId)
                ->sum('max_contribution_score');

            $percentage = $max > 0 ? ($score / $max) * 100 : 0;

            if ($percentage < 75) {
                $recommendations[] = [
                    'competency_id' => $compId,
                    'type' => 'material',
                    'title' => "Review {$comp->name}",
                    'description' => "Perdalam {$comp->name} (skor: " . round($percentage, 0) . "%)",
                    'url' => '#',
                    'priority' => $percentage < 60 ? 'high' : 'medium'
                ];
            }
        }
        return $recommendations;
    }

    private function saveRecommendations($submission_id, $recommendations)
    {
        // Hapus rekomendasi lama sebelum memasukkan yang baru
        DB::table('learning_recommendations')->where('task_submission_id', $submission_id)->delete();

        foreach ($recommendations as $rec) {
            DB::table('learning_recommendations')->insert([
                'task_submission_id' => $submission_id,
                'competency_id' => $rec['competency_id'],
                'type' => $rec['type'],
                'title' => $rec['title'],
                'description' => $rec['description'],
                'url' => $rec['url'],
                'priority' => $rec['priority'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    private function finalizeSubmission($submission_id, $totalScore)
    {
        DB::table('task_submissions')->where('id', $submission_id)->update([
            'status' => 'pending_review',
            'final_grade' => $totalScore,
            'updated_at' => now()
        ]);
    }

    private function finalizeEmptySubmission($submission_id)
    {
        DB::table('task_submissions')->where('id', $submission_id)->update([
            'status' => 'pending_review',
            'final_grade' => 0,
            'updated_at' => now()
        ]);
    }

    /**
     * API: Get Full Report untuk View Detail
     * GET /api/submissions/{submission_id}/report
     */
    public function getStudentReport($submission_id)
    {
        try {
            $data = $this->getFullReportData($submission_id);
            if (isset($data['error'])) {
                return response()->json(['success' => false, 'message' => $data['error']], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            Log::error("Error getting student report {$submission_id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data laporan'], 500);
        }
    }

    private function getFullReportData($submission_id)
    {
        // SUBMISSION INFO
        $submission = DB::table('task_submissions as ts')
            ->join('tasks as t', 'ts.task_id', '=', 't.id')
            ->join('subjects as s', 't.subject_id', '=', 's.id')
            ->join('users as u', 'ts.student_id', '=', 'u.id')
            ->leftJoin('user_details as ud', 'u.id', '=', 'ud.user_id')
            ->join('classes as c', 't.class_id', '=', 'c.id')
            ->where('ts.id', $submission_id)
            ->select(
                'ts.*',
                't.title as task_title',
                't.type as task_type',
                't.description as task_description',
                's.name as subject_name',
                'u.name as student_name',
                'u.email',
                'ud.identity_number as student_nis',
                'c.name as class_name'
            )
            ->first();

        if (!$submission) {
            return ['error' => 'Submission not found']; // Handle jika submission tidak ada
        }

        $submissionStatistic = DB::table('submission_statistics')
            ->where('task_submission_id', $submission_id)
            ->first();

        // COMPETENCIES
        $competencies = [];
        if ($submissionStatistic) {
            $competencies = DB::table('submission_competency_scores as scs')
                ->join('competencies as c', 'scs.competency_id', '=', 'c.id')
                ->where('scs.submission_statistic_id', $submissionStatistic->id)
                ->select(
                    'c.id as competency_id',
                    'c.name',
                    'c.description',
                    'scs.score as score_awarded',
                    'scs.submission_statistic_id' // Ambil untuk join ke task_id
                )
                ->get()
                ->map(function ($c) use ($submission_id) { // Pass submission_id
                    // Ambil max_score terpisah
                    $max_score = DB::table('question_competency_allocations as qca')
                        ->join('questions as q', 'qca.question_id', '=', 'q.id')
                        ->join('tasks as t', 'q.task_id', '=', 't.id')
                        ->join('task_submissions as ts', 't.id', '=', 'ts.task_id')
                        ->where('ts.id', $submission_id) // Gunakan submission_id
                        ->where('qca.competency_id', $c->competency_id)
                        ->sum('qca.max_contribution_score');

                    $percentage = $max_score > 0 ? (($c->score_awarded ?? 0) / $max_score) * 100 : 0;
                    return [
                        'name' => $c->name,
                        'description' => $c->description,
                        'score_awarded' => round($c->score_awarded ?? 0, 1),
                        'max_score' => round($max_score, 1),
                        'percentage' => round($percentage, 1),
                        'level' => $this->getCompetencyLevel($percentage)
                    ];
                });
        }


        // ANSWERS
        $answers = DB::table('task_submission_answers as tsa')
            ->join('questions as q', 'tsa.question_id', '=', 'q.id')
            ->leftJoin('question_options as qo', 'tsa.question_option_id', '=', 'qo.id')
            ->where('tsa.task_submission_id', $submission_id)
            ->select(
                'q.order as question_number',
                'q.question_text',
                'q.type',
                'q.score as max_score',
                'tsa.answer_text',
                'tsa.question_option_id',
                'qo.option_text as selected_option',
                'tsa.ai_suggested_score',
                'tsa.ai_feedback',
                'tsa.score_awarded',
                'tsa.teacher_comment',
                'tsa.is_correct'
            )
            ->orderBy('q.order')
            ->get()
            ->map(function ($a) {
                $percentage = $a->max_score > 0 ? (($a->score_awarded ?? 0) / $a->max_score) * 100 : 0;
                return array_merge((array) $a, [
                    'percentage' => round($percentage, 1),
                    'score_final' => $a->score_awarded ?? $a->ai_suggested_score ?? 0
                ]);
            });

        // QUESTION TYPES SUMMARY
        $questionTypes = $answers->groupBy('type')->map(function ($group, $type) {
            $scored = $group->sum('score_final');
            $max = $group->sum('max_score');
            $percentage = $max > 0 ? ($scored / $max) * 100 : 0;
            return [
                'type' => $type,
                'scored' => round($scored, 1),
                'max' => $max,
                'percentage' => round($percentage, 1)
            ];
        })->values(); // Kirim sebagai array

        // RECOMMENDATIONS
        $recommendations = DB::table('learning_recommendations')
            ->where('task_submission_id', $submission_id)
            ->where('priority', '!=', 'low')
            ->pluck('title')
            ->toArray();

        // RANK & AVERAGE (dari task_class_statistics)
        $stats = DB::table('task_class_statistics')
            ->join('tasks', 'task_class_statistics.task_id', '=', 'tasks.id')
            ->where('tasks.id', $submission->task_id) // Join berdasarkan task_id
            ->select('average_score', 'total_students')
            ->first();

        $submission->rank = $this->calculateRank($submission_id, $submission->final_grade);
        $submission->class_average = $stats->average_score ?? 0;
        $submission->total_students = $stats->total_students ?? 0;

        return [
            'submission' => $submission,
            'competencies' => $competencies,
            'answers' => $answers,
            'question_types' => $questionTypes,
            'recommendations' => $recommendations
        ];
    }

    private function getCompetencyLevel($percentage)
    {
        if ($percentage >= 85)
            return 'Sangat Baik';
        if ($percentage >= 70)
            return 'Baik';
        if ($percentage >= 60)
            return 'Cukup';
        return 'Perlu Perbaikan';
    }

    private function calculateRank($submission_id, $score)
    {
        // Pastikan $score tidak null, jika null anggap 0
        $score = $score ?? 0;

        $taskId = DB::table('task_submissions')->where('id', $submission_id)->value('task_id');

        if (!$taskId) {
            return 0; // Tidak bisa menghitung rank jika task_id tidak ada
        }

        return DB::table('task_submissions')
            ->where('task_id', $taskId) // Filter berdasarkan task_id
            ->where('final_grade', '>=', $score)
            ->whereIn('status', ['graded', 'pending_review']) // Hitung juga yang sudah dinilai AI
            ->count();
    }

    /**
     * Router untuk memanggil AI engine yang sesuai
     */
    private function callAIEngine($provider, $answer, $submission, $model = null)
    {
        switch ($provider) {
            case 'claude':
                return $this->callClaudeAPI($answer, $submission, $model);
            case 'gemini':
                return $this->callGeminiAPI($answer, $submission, $model);
            case 'openai':
                return $this->callOpenAIAPI($answer, $submission, $model);
            default:
                return [
                    'success' => false,
                    'error' => "Provider '{$provider}' tidak didukung"
                ];
        }
    }

    /**
     * ==========================================
     * ANTHROPIC CLAUDE API
     * ==========================================
     */
    private function callClaudeAPI($answer, $submission, $model = null)
    {
        try {
            $apiKey = env('ANTHROPIC_API_KEY');
            if (!$apiKey) {
                throw new Exception('ANTHROPIC_API_KEY tidak ditemukan di .env');
            }

            $model = $model ?? self::AI_PROVIDERS['claude']['default_model'];
            $prompt = $this->buildPrompt($answer, $submission);

            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01'
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $model,
                    'max_tokens' => 2048,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $aiText = $result['content'][0]['text'] ?? '';

                $parsed = $this->parseAIResponse($aiText, $answer->max_score, $answer);

                // Tambahkan metadata dari response
                if ($parsed['success']) {
                    // Ganti nama 'raw_data' menjadi 'raw_results'
                    $parsed['raw_results'] = [
                        'provider' => 'claude', // Tambahkan provider
                        'model' => $model,
                        'raw_response' => $aiText,
                        'metadata' => [
                            'usage' => $result['usage'] ?? null,
                            'stop_reason' => $result['stop_reason'] ?? null
                        ]
                    ];
                }

                return $parsed;
            } else {
                Log::error('Claude API error: ' . $response->body());
                return [
                    'success' => false,
                    'error' => 'API request failed: ' . $response->status()
                ];
            }

        } catch (Exception $e) {
            Log::error('Error calling Claude API: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ==========================================
     * GOOGLE GEMINI API
     * ==========================================
     */
    private function callGeminiAPI($answer, $submission, $model = null)
    {
        try {
            $apiKey = env('GEMINI_API_KEY');
            if (!$apiKey) {
                throw new Exception('GEMINI_API_KEY tidak ditemukan di .env');
            }

            $model = $model ?? self::AI_PROVIDERS['gemini']['default_model'];
            $prompt = $this->buildPrompt($answer, $submission);

            // ==================================================================
            // NOTE: Pindahkan API Key dari URL ke Header 'X-goog-api-key'
            // ==================================================================

            // 1. URL sekarang bersih tanpa API key
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $apiKey // 2. Tambahkan API key di header
                ])
                ->post($url, [ // 3. Hapus "?key=" dari URL
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2048,
                    ]
                ]);

            if ($response->successful()) {
                $result = $response->json();

                // Cek apakah 'candidates' ada
                if (!isset($result['candidates']) || empty($result['candidates'])) {
                    // Ini terjadi jika Safety Filter memblokir respons
                    Log::warning('Gemini API success but no candidates: ', $result);
                    return [
                        'success' => false,
                        'error' => 'AI returned no response (Safety block). Raw: ' . json_encode($result)
                    ];
                }

                $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

                $parsed = $this->parseAIResponse($aiText, $answer->max_score, $answer);

                // Tambahkan metadata dari response
                if ($parsed['success']) {
                    // Ganti nama 'raw_data' menjadi 'raw_results'
                    $parsed['raw_results'] = [
                        'provider' => 'gemini', // Tambahkan provider
                        'model' => $model,
                        'raw_response' => $aiText,
                        'metadata' => [
                            'finish_reason' => $result['candidates'][0]['finishReason'] ?? null,
                            'safety_ratings' => $result['candidates'][0]['safetyRatings'] ?? null
                        ]
                    ];
                }

                return $parsed;
            } else {
                Log::error('Gemini API error: ' . $response->body());
                return [
                    'success' => false,
                    'error' => 'API request failed: ' . $response->status() . ' - ' . $response->body()
                ];
            }

        } catch (Exception $e) {
            Log::error('Error calling Gemini API: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ==========================================
     * OPENAI GPT API
     * ==========================================
     */
    private function callOpenAIAPI($answer, $submission, $model = null)
    {
        try {
            $apiKey = env('OPENAI_API_KEY');
            if (!$apiKey) {
                throw new Exception('OPENAI_API_KEY tidak ditemukan di .env');
            }

            $model = $model ?? self::AI_PROVIDERS['openai']['default_model'];
            $prompt = $this->buildPrompt($answer, $submission);

            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Anda adalah asisten penilai akademik yang ahli dan objektif.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 2048,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $aiText = $result['choices'][0]['message']['content'] ?? '';

                $parsed = $this->parseAIResponse($aiText, $answer->max_score, $answer);

                // Tambahkan metadata dari response
                if ($parsed['success']) {
                    // Ganti nama 'raw_data' menjadi 'raw_results'
                    $parsed['raw_results'] = [
                        'provider' => 'openai', // Tambahkan provider
                        'model' => $model,
                        'raw_response' => $aiText,
                        'metadata' => [
                            'usage' => $result['usage'] ?? null,
                            'finish_reason' => $result['choices'][0]['finish_reason'] ?? null
                        ]
                    ];
                }

                return $parsed;
            } else {
                Log::error('OpenAI API error: '->body());
                return [
                    'success' => false,
                    'error' => 'API request failed: ' . $response->status()
                ];
            }

        } catch (Exception $e) {
            Log::error('Error calling OpenAI API: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Membangun prompt untuk AI (Universal untuk semua provider)
     */
    private function buildPrompt($answer, $submission)
    {
        $prompt = "Anda adalah asisten penilai akademik yang ahli. Tugas Anda adalah menilai jawaban siswa secara objektif dan memberikan feedback konstruktif.\n\n";

        $prompt .= "KONTEKS TUGAS:\n";
        $prompt .= "Tugas: {$submission->task_title}\n";
        if ($submission->task_description) {
            $prompt .= "Deskripsi: {$submission->task_description}\n";
        }
        $prompt .= "Siswa: {$submission->student_name}\n\n";

        $prompt .= "PERTANYAAN (Tipe: {$answer->question_type}):\n{$answer->question_text}\n\n";

        if ($answer->explanation) {
            $prompt .= "PEMBAHASAN/KUNCI JAWABAN:\n{$answer->explanation}\n\n";
        }

        // Jika Pilihan Ganda, tunjukkan jawaban yang benar
        if ($answer->question_type === 'multiple_choice' && $answer->correct_answer_text) {
            $prompt .= "PILIHAN JAWABAN BENAR:\n{$answer->correct_answer_text}\n\n";
        }

        // Jika Isian Singkat, tunjukkan juga jawaban yang diharapkan
        if ($answer->correct_answer_text && $answer->question_type === 'short_answer') {
            $prompt .= "JAWABAN YANG DIHARAPKAN:\n{$answer->correct_answer_text}\n\n";
        }

        $prompt .= "JAWABAN SISWA:\n";
        // Untuk Pilihan Ganda, kita perlu mengambil teks dari question_option_id
        if ($answer->question_type === 'multiple_choice' && $answer->question_option_id) {
            $studentOption = DB::table('question_options')->where('id', $answer->question_option_id)->value('option_text');
            $prompt .= ($studentOption ?? "N/A (ID: {$answer->question_option_id})") . "\n\n";
        } else {
            $prompt .= ($answer->answer_text ?? "Tidak ada jawaban") . "\n\n";
        }

        $prompt .= "INSTRUKSI PENILAIAN:\n";
        $prompt .= "1. Skor maksimum: {$answer->max_score} poin\n";
        $prompt .= "2. Berikan skor dalam bentuk desimal (contoh: 7.5)\n";
        $prompt .= "3. Berikan feedback yang:\n";
        $prompt .= "   - Konstruktif dan membangun\n";
        $prompt .= "   - Menjelaskan apa yang sudah benar\n";
        $prompt .= "   - Menjelaskan apa yang perlu diperbaiki\n";
        $prompt .= "   - Memberikan saran konkret untuk perbaikan\n";
        $prompt .= "4. Untuk Pilihan Ganda: Jika jawaban siswa SAMA DENGAN Pilihan Jawaban Benar, berikan skor MAKSIMUM. Jika tidak, berikan skor 0.\n\n";

        $prompt .= "FORMAT JAWABAN (HARUS PERSIS):\n";
        $prompt .= "SKOR: [angka]\n";
        $prompt .= "FEEDBACK: [teks feedback Anda]\n\n";

        $prompt .= "Contoh untuk Esai:\n";
        $prompt .= "SKOR: 7.5\n";
        $prompt .= "FEEDBACK: Jawaban Anda sudah menunjukkan pemahaman yang baik tentang konsep utama. Namun, penjelasan tentang mekanisme bisa lebih detail. Coba tambahkan contoh konkret untuk memperkuat argumen Anda.\n\n";

        $prompt .= "Contoh untuk Pilihan Ganda (Benar):\n";
        $prompt .= "SKOR: {$answer->max_score}\n";
        $prompt .= "FEEDBACK: Jawaban Anda benar!\n\n";

        $prompt .= "Contoh untuk Pilihan Ganda (Salah):\n";
        $prompt .= "SKOR: 0\n";
        $prompt .= "FEEDBACK: Jawaban Anda kurang tepat. Jawaban yang benar adalah '{$answer->correct_answer_text}'.\n";

        return $prompt;
    }

    /**
     * Parse response dari AI (Universal untuk semua provider)
     */
    private function parseAIResponse($aiText, $maxScore, $answer) // Tambahkan $answer
    {
        try {
            // Extract SKOR
            preg_match('/SKOR:\s*([\d.]+)/i', $aiText, $scoreMatches);
            $score = isset($scoreMatches[1]) ? floatval($scoreMatches[1]) : 0;

            // Pastikan score tidak melebihi max_score
            $score = min($score, $maxScore);
            $score = max($score, 0); // Tidak boleh negatif

            // Extract FEEDBACK
            preg_match('/FEEDBACK:\s*(.+)/is', $aiText, $feedbackMatches);
            $feedback = isset($feedbackMatches[1]) ? trim($feedbackMatches[1]) : 'Tidak ada feedback tersedia.';

            // Fallback jika format tidak ditemukan
            if (empty($scoreMatches) && empty($feedbackMatches)) {
                $feedback = $aiText; // Gunakan teks mentah jika parse gagal
            }

            // Tambahkan flag 'is_correct' khusus untuk Pilihan Ganda
            $isCorrect = null;
            if ($answer->question_type === 'multiple_choice') {
                // Tentukan 'is_correct' berdasarkan skor yang diberikan AI
                $isCorrect = $score >= $maxScore;
            }

            return [
                'success' => true,
                'score' => $score,
                'feedback' => $feedback,
                'is_correct' => $isCorrect // Kembalikan nilai is_correct
            ];

        } catch (Exception $e) {
            Log::error('Error parsing AI response: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Gagal mem-parse response AI'
            ];
        }
    }

    /**
     * Helper: Ambil data submission yang sudah diupdate untuk response
     */
    private function getUpdatedSubmissionData($submission_id)
    {
        $submission = DB::table('task_submissions as ts')
            ->join('users as u', 'ts.student_id', '=', 'u.id')
            ->leftJoin('user_details as ud', 'u.id', '=', 'ud.user_id')
            ->join('tasks as t', 'ts.task_id', '=', 't.id')
            ->where('ts.id', $submission_id)
            ->select(
                'ts.id as submission_id',
                'ts.status',
                'ts.final_grade as score',
                'ts.submitted_at',
                'u.id as student_id',
                'u.name',
                'u.email',
                'ud.identity_number',
                't.end_time'
            )
            ->first();

        if (!$submission) {
            return null;
        }

        // Format sesuai dengan yang diharapkan DataTables
        $submission->submitted_at_formatted = $submission->submitted_at
            ? Carbon::parse($submission->submitted_at)->format('d M Y, H:i')
            : 'N/A';

        $submission->status_raw = $submission->status;

        // Set badge dan text berdasarkan status
        switch ($submission->status) {
            case 'submitted':
                $submission->status_pengerjaan = 'Terkumpul (Belum Dinilai)';
                $submission->status_badge = 'bg-primary';
                break;
            case 'late':
                $submission->status_pengerjaan = 'Terlambat (Belim Dinilai)';
                $submission->status_badge = 'bg-warning text-dark';
                break;
            case 'ai_processing':
                $submission->status_pengerjaan = 'Sedang Diproses AI';
                $submission->status_badge = 'bg-info';
                break;
            case 'pending_review':
                $submission->status_pengerjaan = 'Perlu Direview Guru';
                $submission->status_badge = 'bg-warning';
                break;
            case 'graded':
                $submission->status_pengerjaan = 'Sudah Dinilai';
                $submission->status_badge = 'bg-success';
                break;
            default:
                $submission->status_pengerjaan = 'Status Tidak Dikenal';
                $submission->status_badge = 'bg-secondary';
        }

        return $submission;
    }

    /**
     * API: Mendapatkan daftar AI providers yang tersedia
     * GET /api/ai-providers
     */
    public function getAvailableProviders()
    {
        $providers = [];

        foreach (self::AI_PROVIDERS as $key => $config) {
            // Cek apakah API key tersedia
            $apiKeyEnv = strtoupper($key) . '_API_KEY';
            $isConfigured = !empty(env($apiKeyEnv));

            $providers[] = [
                'id' => $key,
                'name' => $config['name'],
                'configured' => $isConfigured,
                'models' => $config['models'],
                'default_model' => $config['default_model']
            ];
        }

        return response()->json([
            'success' => true,
            'providers' => $providers,
            'default_provider' => env('AI_GRADING_PROVIDER', 'claude')
        ]);
    }

    /**
     * API: Mendapatkan detail raw results dari AI
     * GET /api/answers/{answer_id}/ai-raw-results
     */
    public function getAIRawResults($answer_id)
    {
        try {
            $answer = DB::table('task_submission_answers')
                ->where('id', $answer_id)
                ->select('ai_raw_results', 'ai_suggested_score', 'ai_feedback', 'ai_processing_status')
                ->first();

            if (!$answer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Answer tidak ditemukan'
                ], 404);
            }

            $rawResults = $answer->ai_raw_results ? json_decode($answer->ai_raw_results, true) : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'ai_suggested_score' => $answer->ai_suggested_score,
                    'ai_feedback' => $answer->ai_feedback,
                    'ai_processing_status' => $answer->ai_processing_status,
                    'raw_results' => $rawResults
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error getting AI raw results: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data'
            ], 500);
        }
    }

    /**
     * API: Statistik penggunaan AI per provider
     * GET /api/ai-statistics
     */
    public function getAIStatistics(Request $request)
    {
        try {
            // Ambil filter dari request (optional)
            $taskId = $request->query('task_id');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            // Base query
            $query = DB::table('task_submission_answers as tsa')
                ->join('task_submissions as ts', 'tsa.task_submission_id', '=', 'ts.id')
                ->where('tsa.ai_processing_status', 'completed')
                ->whereNotNull('tsa.ai_raw_results');

            // Apply filters
            if ($taskId) {
                $query->where('ts.task_id', $taskId);
            }

            if ($dateFrom) {
                $query->where('tsa.updated_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('tsa.updated_at', '<=', $dateTo);
            }

            $answers = $query->select('tsa.ai_raw_results', 'tsa.ai_suggested_score', 'tsa.score_awarded')
                ->get();

            $total_processed = $answers->count();

            // Aggregate statistics
            $stats = [
                'total_processed' => $total_processed,
                'by_provider' => [],
                'by_model' => [],
                'accuracy' => [
                    'avg_difference' => 0,
                    'approval_rate' => 0
                ]
            ];

            // Handle jika tidak ada data
            if ($total_processed === 0) {
                return response()->json([
                    'success' => true,
                    'data' => $stats
                ]);
            }

            $providerCounts = [];
            $modelCounts = [];
            $totalDifference = 0;
            $approvalCount = 0;
            $processedWithScore = 0; // Hitung hanya yang punya skor untuk akurasi

            foreach ($answers as $answer) {
                $raw = json_decode($answer->ai_raw_results, true);

                // Periksa apakah 'raw_results' ada dan punya 'provider'
                if ($raw && isset($raw['provider'])) {
                    $provider = $raw['provider'];
                    $providerCounts[$provider] = ($providerCounts[$provider] ?? 0) + 1;

                    if (isset($raw['model'])) {
                        $model = $raw['model'];
                        $modelCounts[$model] = ($modelCounts[$model] ?? 0) + 1;
                    }
                } else if ($raw && $raw['type'] === 'multiple_choice' && $raw['provider'] === 'auto_grade') {
                    // Handle legacy auto_grade jika masih ada di DB
                    $provider = 'auto_grade';
                    $providerCounts[$provider] = ($providerCounts[$provider] ?? 0) + 1;
                }

                // Calculate accuracy
                if ($answer->score_awarded !== null && $answer->ai_suggested_score !== null) {
                    $diff = abs($answer->score_awarded - $answer->ai_suggested_score);
                    $totalDifference += $diff;
                    $processedWithScore++;

                    // Consider "approved" if difference <= 1 point (atau 0 untuk PG)
                    $threshold = ($raw['type'] ?? 'essay') === 'multiple_choice' ? 0 : 1;
                    if ($diff <= $threshold) {
                        $approvalCount++;
                    }
                }
            }

            // Format statistics
            foreach ($providerCounts as $provider => $count) {
                $stats['by_provider'][] = [
                    'provider' => $provider,
                    'count' => $count,
                    'percentage' => round(($count / $total_processed) * 100, 2)
                ];
            }

            foreach ($modelCounts as $model => $count) {
                $stats['by_model'][] = [
                    'model' => $model,
                    'count' => $count,
                    'percentage' => round(($count / $total_processed) * 100, 2)
                ];
            }

            if ($processedWithScore > 0) {
                $stats['accuracy']['avg_difference'] = round($totalDifference / $processedWithScore, 2);
                $stats['accuracy']['approval_rate'] = round(($approvalCount / $processedWithScore) * 100, 2);
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Error getting AI statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik'
            ], 500);
        }
    }
}
