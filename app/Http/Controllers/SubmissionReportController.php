<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class SubmissionReportController extends Controller
{
    /**
     * Menampilkan halaman laporan detail (wrapper)
     * GET /classes/{class_id}/tasks/{task_id}/submissions/{submission_id}/report
     */
    public function showReport($class_id, $task_id, $submission_id)
    {
        // Verifikasi submission exists
        $exists = DB::table('task_submissions')
            ->where('id', $submission_id)
            ->where('task_id', $task_id)
            ->exists();

        if (!$exists) {
            abort(404, 'Laporan tidak ditemukan.');
        }

        return view('task.report', [
            'class_id' => $class_id,
            'task_id' => $task_id,
            'submission_id' => $submission_id
        ]);
    }

    /**
     * API: Mengambil data lengkap untuk laporan
     * GET /api/submissions/{submission_id}/report-data
     */
    public function getReportData($submission_id)
    {
        try {
            // 1. Data Siswa & Submission Utama
            $submission = DB::table('task_submissions as ts')
                ->join('users as u', 'ts.student_id', '=', 'u.id')
                ->leftJoin('user_details as ud', 'u.id', '=', 'ud.user_id')
                ->join('tasks as t', 'ts.task_id', '=', 't.id')
                ->join('classes as c', 't.class_id', '=', 'c.id')
                ->join('subjects as s', 't.subject_id', '=', 's.id')
                ->leftJoin('users as grader', 'ts.graded_by', '=', 'grader.id')
                ->leftJoin('academic_years as ay', 'c.academic_year_id', '=', 'ay.id')
                ->where('ts.id', $submission_id)
                ->select(
                    // Submission
                    'ts.id as submission_id',
                    'ts.submitted_at',
                    'ts.duration_seconds',
                    'ts.final_grade',
                    'ts.teacher_feedback',
                    'ts.status',
                    'ts.rank',
                    'ts.class_average',
                    'ts.graded_at',
                    // Student
                    'u.name as student_name',
                    'u.email as student_email',
                    'ud.identity_number as student_nis',
                    // Task
                    't.id as task_id',
                    't.title as task_title',
                    't.description as task_description',
                    't.type as task_type',
                    't.end_time',
                    // Class & Subject
                    'c.name as class_name',
                    's.name as subject_name',
                    // Grader
                    'grader.name as grader_name',
                    // Academic Year
                    'ay.name as academic_year_name',
                    'ay.semester'
                )
                ->first();

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan.'
                ], 404);
            }

            // 2. Hitung total siswa di kelas untuk ranking
            $totalStudents = DB::table('task_submissions')
                ->where('task_id', $submission->task_id)
                ->whereNotNull('final_grade')
                ->count();

            // 3. Statistik Submission
            $submissionStats = DB::table('submission_statistics')
                ->where('task_submission_id', $submission_id)
                ->first();

            // 4. Data Jawaban Lengkap
            $answers = DB::table('task_submission_answers as tsa')
                ->join('questions as q', 'tsa.question_id', '=', 'q.id')
                ->where('tsa.task_submission_id', $submission_id)
                ->select(
                    'tsa.id as answer_id',
                    'tsa.question_id',
                    'tsa.answer_text',
                    'tsa.question_option_id',
                    'tsa.ai_suggested_score',
                    'tsa.ai_feedback',
                    'tsa.score_awarded',
                    'tsa.teacher_comment',
                    'tsa.is_correct',
                    'q.question_text',
                    'q.type as question_type',
                    'q.score as max_score',
                    'q.order',
                    'q.explanation'
                )
                ->orderBy('q.order')
                ->get();

            // 5. Proses setiap jawaban
            $processedAnswers = [];
            $questionTypeScores = [
                'multiple_choice' => ['scored' => 0, 'max' => 0],
                'essay' => ['scored' => 0, 'max' => 0],
                'short_answer' => ['scored' => 0, 'max' => 0]
            ];

            foreach ($answers as $index => $answer) {
                $answerData = [
                    'question_number' => $index + 1,
                    'question_text' => $answer->question_text,
                    'type' => $answer->question_type,
                    'max_score' => $answer->max_score,
                    'score_awarded' => $answer->score_awarded ?? 0,
                    'ai_suggested_score' => $answer->ai_suggested_score,
                    'ai_feedback' => $answer->ai_feedback,
                    'teacher_comment' => $answer->teacher_comment,
                    'is_correct' => $answer->is_correct,
                    'explanation' => $answer->explanation
                ];

                // Update statistik per tipe soal
                if (isset($questionTypeScores[$answer->question_type])) {
                    $questionTypeScores[$answer->question_type]['scored'] += ($answer->score_awarded ?? 0);
                    $questionTypeScores[$answer->question_type]['max'] += $answer->max_score;
                }

                // Format jawaban berdasarkan tipe
                if ($answer->question_type === 'multiple_choice') {
                    $options = DB::table('question_options')
                        ->where('question_id', $answer->question_id)
                        ->orderBy('order')
                        ->get();

                    $selectedOption = DB::table('question_options')
                        ->where('id', $answer->question_option_id)
                        ->first();

                    $answerData['options'] = $options;
                    $answerData['selected_option'] = $selectedOption ? $selectedOption->option_text : null;
                    $answerData['student_answer'] = $selectedOption ? $selectedOption->option_text : '(Tidak dijawab)';

                } else {
                    $answerData['answer_text'] = $answer->answer_text ?? '(Tidak dijawab)';
                    $answerData['student_answer'] = $answer->answer_text ?? '(Tidak dijawab)';
                }

                // Ambil evaluasi kompetensi untuk jawaban ini
                $competencyEvals = DB::table('answer_competency_evaluations as ace')
                    ->join('competencies as c', 'ace.competency_id', '=', 'c.id')
                    ->where('ace.task_submission_answer_id', $answer->answer_id)
                    ->select(
                        'c.name as competency_name',
                        'ace.score_awarded',
                        DB::raw('(SELECT SUM(max_contribution_score)
                                  FROM question_competency_allocations
                                  WHERE question_id = ' . $answer->question_id . '
                                  AND competency_id = ace.competency_id) as max_score')
                    )
                    ->get();

                $answerData['competency_evaluations'] = $competencyEvals;

                $processedAnswers[] = $answerData;
            }

            // 6. Hitung persentase per tipe soal
            foreach ($questionTypeScores as $type => &$scores) {
                if ($scores['max'] > 0) {
                    $scores['percentage'] = ($scores['scored'] / $scores['max']) * 100;
                } else {
                    $scores['percentage'] = 0;
                }
            }

            // 7. Agregasi Skor Kompetensi
            $competencyScores = DB::table('answer_competency_evaluations as ace')
                ->join('competencies as c', 'ace.competency_id', '=', 'c.id')
                ->join('task_submission_answers as tsa', 'ace.task_submission_answer_id', '=', 'tsa.id')
                ->where('tsa.task_submission_id', $submission_id)
                ->select(
                    'c.id as competency_id',
                    'c.name as competency_name',
                    'c.description as competency_description',
                    'c.level as competency_level',
                    DB::raw('SUM(ace.score_awarded) as total_score_awarded')
                )
                ->groupBy('c.id', 'c.name', 'c.description', 'c.level')
                ->get();

            // Hitung max score per kompetensi
            $competencies = [];
            foreach ($competencyScores as $comp) {
                $maxScore = DB::table('question_competency_allocations as qca')
                    ->join('task_submission_answers as tsa', 'qca.question_id', '=', 'tsa.question_id')
                    ->where('tsa.task_submission_id', $submission_id)
                    ->where('qca.competency_id', $comp->competency_id)
                    ->sum('qca.max_contribution_score');

                $percentage = $maxScore > 0 ? ($comp->total_score_awarded / $maxScore) * 100 : 0;

                // Tentukan level pencapaian
                $level = 'Perlu Perbaikan';
                if ($percentage >= 90)
                    $level = 'Istimewa';
                elseif ($percentage >= 85)
                    $level = 'Sangat Baik';
                elseif ($percentage >= 70)
                    $level = 'Baik';
                elseif ($percentage >= 60)
                    $level = 'Cukup';

                $competencies[] = [
                    'name' => $comp->competency_name,
                    'description' => $comp->competency_description,
                    'score_awarded' => $comp->total_score_awarded,
                    'max_score' => $maxScore,
                    'percentage' => round($percentage, 1),
                    'level' => $level
                ];
            }

            // 8. Rekomendasi Pembelajaran
            $recommendations = DB::table('learning_recommendations')
                ->where('task_submission_id', $submission_id)
                ->orderBy('priority', 'desc')
                ->get();

            // 9. Format data untuk frontend
            $reportData = [
                'student' => [
                    'name' => $submission->student_name,
                    'nis' => $submission->student_nis ?? 'N/A',
                    'class' => $submission->class_name,
                    'email' => $submission->student_email,
                    'status' => 'Siswa Aktif'
                ],
                'task' => [
                    'title' => $submission->task_title,
                    'type' => strtoupper($submission->task_type),
                    'subject' => $submission->subject_name,
                    'description' => $submission->task_description,
                    'teacher' => $submission->grader_name ?? 'Belum dinilai',
                    'total_questions' => $answers->count(),
                    'total_score' => $answers->sum('max_score')
                ],
                'submission' => [
                    'submitted_at' => $submission->submitted_at,
                    'duration' => $this->formatDuration($submission->duration_seconds),
                    'final_grade' => $submission->final_grade ?? 0,
                    'status' => $submission->status,
                    'is_late' => Carbon::parse($submission->submitted_at)->isAfter($submission->end_time),
                    'late_days' => Carbon::parse($submission->submitted_at)->diffInDays($submission->end_time, false),
                    'answered_count' => $answers->count(),
                    'rank' => $submission->rank ?? 0,
                    'total_students' => $totalStudents,
                    'class_average' => $submission->class_average ?? 0,
                    'teacher_feedback' => $submission->teacher_feedback,
                    'learning_recommendations' => $recommendations->pluck('title')->toArray()
                ],
                'competencies' => $competencies,
                'questionTypes' => $questionTypeScores,
                'answers' => $processedAnswers,
                'academic_year' => $submission->academic_year_name ?? 'N/A',
                'semester' => $submission->semester === 'odd' ? 'Ganjil' : 'Genap'
            ];

            return response()->json([
                'success' => true,
                'data' => $reportData
            ]);

        } catch (Exception $e) {
            Log::error('Error generating report for submission ' . $submission_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data laporan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Format durasi dari detik ke format yang readable
     */
    private function formatDuration($seconds)
    {
        if (!$seconds)
            return 'N/A';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($hours > 0)
            $parts[] = "{$hours} jam";
        if ($minutes > 0)
            $parts[] = "{$minutes} menit";
        if ($secs > 0 || empty($parts))
            $parts[] = "{$secs} detik";

        return implode(' ', $parts);
    }
}
