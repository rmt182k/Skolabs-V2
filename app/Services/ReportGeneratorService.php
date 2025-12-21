<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\AIEngineService;

class ReportGeneratorService
{
    protected $aiEngine;

    // Inject AI Engine
    public function __construct(AIEngineService $aiEngine)
    {
        $this->aiEngine = $aiEngine;
    }

    /**
     * Generate Laporan Lengkap (Statistik & Kompetensi) via AI
     */
    public function generateStudentReport($submissionId)
    {
        // Pastikan submission sudah dinilai (graded)
        $submission = DB::table('task_submissions')->where('id', $submissionId)->first();
        if (!$submission || $submission->status !== 'graded') {
            return ['success' => false, 'message' => 'Tugas belum dinilai guru.'];
        }

        DB::beginTransaction();
        try {
            // 1. Ambil Data Lengkap (Soal, Jawaban, Nilai Guru) untuk dikirim ke AI
            $data = $this->prepareDataForAI($submissionId);

            // 2. Minta AI Menganalisis Kompetensi & Rekomendasi
            // Ini menggantikan fungsi manual analyzeCompetencies()
            $aiAnalysis = $this->aiEngine->generateCompetencyReport(
                $data['submission'],
                $data['answers'],
                $data['subject_name']
            );

            if (!$aiAnalysis) {
                throw new \Exception("Gagal mendapatkan analisis dari AI.");
            }

            // 3. Hitung Statistik Dasar (Angka Pasti: Jumlah Soal, Total Skor, dll)
            // Kita tetap hitung ini secara manual agar data numerik header akurat 100%
            $stats = $this->calculateBasicStatistics($submissionId);

            // Simpan/Update Statistik Utama
            DB::table('submission_statistics')->updateOrInsert(
                ['task_submission_id' => $submissionId],
                array_merge($stats, ['updated_at' => now()])
            );

            // Ambil ID statistik (parent key untuk kompetensi)
            $statId = DB::table('submission_statistics')->where('task_submission_id', $submissionId)->value('id');

            // 4. Simpan Kompetensi Hasil Analisis AI
            // Hapus data lama agar tidak duplikat saat regenerate
            DB::table('submission_competency_scores')->where('submission_statistic_id', $statId)->delete();

            if (!empty($aiAnalysis['competencies'])) {
                foreach ($aiAnalysis['competencies'] as $compData) {
                    // A. Cari atau Buat Kompetensi Baru (Dynamic Competency Discovery)
                    // Jika AI menemukan kompetensi yang belum ada di master data, buat baru.
                    $compRecord = DB::table('competencies')->where('name', $compData['name'])->first();

                    if (!$compRecord) {
                        $compId = DB::table('competencies')->insertGetId([
                            'name' => $compData['name'],
                            'code' => strtoupper(substr(Str::slug($compData['name']), 0, 8)) . '-' . rand(100, 999),
                            'description' => $compData['description'] ?? 'Kompetensi teridentifikasi oleh AI',
                            'subject_id' => $data['subject_id'],
                            'level' => 'intermediate',
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else {
                        $compId = $compRecord->id;
                    }

                    // B. Simpan Skor Kompetensi (AI memberikan nilai 0-100)
                    DB::table('submission_competency_scores')->insert([
                        'submission_statistic_id' => $statId,
                        'competency_id' => $compId,
                        'score' => $compData['score_percentage'], // Skor langsung dari AI
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // 5. Simpan Rekomendasi Hasil Analisis AI
            DB::table('learning_recommendations')->where('task_submission_id', $submissionId)->delete();

            if (!empty($aiAnalysis['recommendations'])) {
                foreach ($aiAnalysis['recommendations'] as $rec) {
                    DB::table('learning_recommendations')->insert([
                        'task_submission_id' => $submissionId,
                        'competency_id' => null, // Opsional, bisa dilink jika perlu
                        'type' => $rec['type'] ?? 'material',
                        'title' => $rec['title'],
                        'description' => $rec['description'],
                        'url' => '#',
                        'priority' => $rec['priority'] ?? 'medium',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();
            return ['success' => true];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Report Gen Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // --- HELPER FUNCTIONS ---

    /**
     * Menyiapkan data mentah untuk dikirim ke Prompt AI
     */
    private function prepareDataForAI($submissionId)
    {
        $submission = DB::table('task_submissions as ts')
            ->join('tasks as t', 'ts.task_id', '=', 't.id')
            ->join('users as u', 'ts.student_id', '=', 'u.id')
            ->where('ts.id', $submissionId)
            ->select('ts.id', 't.title as task_title', 't.subject_id', 't.description as task_description', 'u.name as student_name')
            ->first();

        $subjectName = DB::table('subjects')->where('id', $submission->subject_id)->value('name');

        // Ambil jawaban beserta nilai final dari guru
        $answers = DB::table('task_submission_answers as tsa')
            ->join('questions as q', 'tsa.question_id', '=', 'q.id')
            ->leftJoin('question_options as qo', 'tsa.question_option_id', '=', 'qo.id')
            ->where('tsa.task_submission_id', $submissionId)
            ->select(
                'q.question_text',
                'q.type',
                'q.score as max_score',
                'tsa.answer_text',
                'qo.option_text as selected_option',
                'tsa.score_awarded', // Ini nilai final dari guru
                'tsa.is_correct'
            )
            ->get();

        return [
            'submission' => $submission,
            'answers' => $answers,
            'subject_name' => $subjectName,
            'subject_id' => $submission->subject_id
        ];
    }

    /**
     * Menghitung statistik numerik dasar (Jumlah Benar, Salah, Skor per Tipe)
     * Ini lebih akurat jika dihitung manual via Query daripada minta AI menghitung.
     */
    private function calculateBasicStatistics($submissionId)
    {
        $answers = DB::table('task_submission_answers as tsa')
            ->join('questions as q', 'tsa.question_id', '=', 'q.id')
            ->where('tsa.task_submission_id', $submissionId)
            ->select('q.type', 'tsa.score_awarded', 'tsa.is_correct')
            ->get();

        return [
            'total_questions' => $answers->count(),
            'answered_questions' => $answers->whereNotNull('score_awarded')->count(),
            'correct_answers' => $answers->where('is_correct', 1)->count(),
            'multiple_choice_score' => $answers->where('type', 'multiple_choice')->sum('score_awarded'),
            'essay_score' => $answers->where('type', 'essay')->sum('score_awarded'),
            'short_answer_score' => $answers->where('type', 'short_answer')->sum('score_awarded'),
            'created_at' => now()
        ];
    }
}
