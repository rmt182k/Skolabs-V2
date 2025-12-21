<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ReportGeneratorService;

class AssessmentReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportGeneratorService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * API untuk memicu pembuatan laporan siswa (Manual Trigger)
     */
    public function generate(Request $request, $submission_id)
    {

        // 1. Ambil Submission
        $submission = DB::table('task_submissions')->find($submission_id);
        if (!$submission) {
            return response()->json(['error' => 'Submission not found'], 404);
        }

        // 2. Validasi Status (Harus Graded/Sudah Dinilai Guru)
        if ($submission->status !== 'graded') {
            return response()->json([
                'error' => 'Submission must be graded by teacher first.'
            ], 400);
        }

        // 3. Generate Report via Service
        $result = $this->reportService->generateStudentReport($submission_id);

        if ($result['success']) {
            return response()->json(['success' => true, 'message' => 'Laporan berhasil dibuat.']);
        } else {
            return response()->json(['success' => false, 'message' => $result['message']], 500);
        }
    }

    /**
     * API untuk mengambil data JSON laporan siswa
     */
    public function getStudentReport($submission_id)
    {
        $submission = DB::table('task_submissions')->find($submission_id);
        if (!$submission)
            return response()->json(['error' => 'Not found'], 404);

        // [MODIFIED] Tidak lagi auto-generate. Hanya mengembalikan data yang ada.

        // Cek apakah statistik sudah ada (optional check)
        // $hasStats = DB::table('submission_statistics')->where('task_submission_id', $submission_id)->exists();

        // Build Data Response
        $data = $this->buildReportData($submission_id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    private function buildReportData($sid)
    {
        // 1. Info Utama
        $submission = DB::table('task_submissions as ts')
            ->join('tasks as t', 'ts.task_id', '=', 't.id')
            ->join('subjects as s', 't.subject_id', '=', 's.id')
            ->join('users as u', 'ts.student_id', '=', 'u.id')
            ->join('classes as c', 't.class_id', '=', 'c.id')
            ->leftJoin('users as teacher', 't.teacher_id', '=', 'teacher.id')
            ->leftJoin('user_details as ud', 'u.id', '=', 'ud.user_id')
            ->where('ts.id', $sid)
            ->select(
                'ts.*',
                't.title as task_title',
                't.type as task_type',
                't.description as task_description',
                's.name as subject_name',
                'c.name as class_name',
                'u.name as student_name',
                'u.email',
                'ud.identity_number as student_nis',
                'teacher.name as teacher_name'
            )->first();

        // 2. Jawaban Detail
        $answers = DB::table('task_submission_answers as tsa')
            ->join('questions as q', 'tsa.question_id', '=', 'q.id')
            ->leftJoin('question_options as qo', 'tsa.question_option_id', '=', 'qo.id')
            ->where('tsa.task_submission_id', $sid)
            ->select(
                'q.order',
                'q.question_text',
                'q.type',
                'q.score as max_score',
                'tsa.answer_text',
                'qo.option_text as selected_option',
                'tsa.score_awarded',
                'tsa.teacher_comment',
                'tsa.ai_feedback',
                'tsa.is_correct',
                'tsa.ai_suggested_score'
            )
            ->orderBy('q.order')
            ->get()
            ->map(function ($a) {
                // Hitung persen skor final vs max untuk styling di frontend
                $a->percentage = $a->max_score > 0 ? ($a->score_awarded / $a->max_score) * 100 : 0;
                $a->score_final = $a->score_awarded; // Untuk konsistensi nama field
                return $a;
            });

        // 3. Kompetensi (AI-Driven Logic)
        $statId = DB::table('submission_statistics')->where('task_submission_id', $sid)->value('id');
        $competencies = [];

        if ($statId) {
            $competencies = DB::table('submission_competency_scores as scs')
                ->join('competencies as c', 'scs.competency_id', '=', 'c.id')
                ->where('scs.submission_statistic_id', $statId)
                ->select('c.name', 'c.description', 'c.level', 'scs.score as percentage_score')
                ->get()
                ->map(function ($c) {
                    // Karena AI sekarang sudah mengisi skor langsung dalam bentuk PERSENTASE (0-100),
                    // Kita tidak perlu lagi menghitung manual (score/max * 100).
                    // Kita langsung gunakan nilai dari DB.

                    $pct = (float) $c->percentage_score;

                    return [
                        'name' => $c->name,
                        'description' => $c->description,
                        'score_awarded' => $pct, // Nilai 0-100
                        'max_score' => 100,      // Max selalu 100%
                        'percentage' => $pct,    // Untuk chart
                        'level' => $this->getCompetencyLevel($pct) // Helper level text
                    ];
                });
        }

        // 4. Rekomendasi
        $recommendations = DB::table('learning_recommendations')
            ->where('task_submission_id', $sid)
            ->pluck('title'); // Hanya ambil judul untuk list simple

        // 5. Question Types Summary (Untuk Donut Chart)
        // Hitung manual dari collection $answers agar cepat (tanpa query ulang)
        $questionTypes = $answers->groupBy('type')->map(function ($group, $type) {
            $scored = $group->sum('score_awarded');
            $max = $group->sum('max_score');
            $percentage = $max > 0 ? ($scored / $max) * 100 : 0;

            // Label mapping (optional, bisa di handle frontend)
            $label = match ($type) {
                'multiple_choice' => 'Pilihan Ganda',
                'essay' => 'Esai',
                'short_answer' => 'Isian Singkat',
                default => ucfirst(str_replace('_', ' ', $type))
            };

            return [
                'label' => $label,
                'scored' => round($scored, 1),
                'max' => $max,
                'percentage' => round($percentage, 1)
            ];
        });

        return compact('submission', 'answers', 'competencies', 'recommendations', 'questionTypes');
    }

    /**
     * Helper untuk menentukan label level berdasarkan skor persentase
     */
    private function getCompetencyLevel($pct)
    {
        if ($pct >= 85)
            return 'Sangat Baik';
        if ($pct >= 70)
            return 'Baik';
        if ($pct >= 50)
            return 'Cukup';
        return 'Perlu Perbaikan';
    }
}
